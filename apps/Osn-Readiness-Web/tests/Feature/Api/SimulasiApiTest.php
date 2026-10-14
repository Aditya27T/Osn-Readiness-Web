<?php

namespace Tests\Feature\Api;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Contracts\Services\LatihanServiceInterface;
use App\Contracts\Services\PretestServiceInterface;
use App\Contracts\Services\SimulasiServiceInterface;
use App\Enums\JenisPengerjaan;
use App\Exceptions\PerhitunganKonfigurasiException;
use App\Jobs\NilaiUlangJob;
use App\Models\HasilSimulasi;
use App\Models\HasilSimulasiJawaban;
use App\Models\KenaikanTingkat;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\ProgressBelajar;
use App\Models\Quiz;
use App\Models\QuizPengerjaan;
use App\Models\RekomendasiMateri;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakePerhitunganClient;
use Tests\TestCase;

class SimulasiApiTest extends TestCase
{
    use RefreshDatabase;

    private FakePerhitunganClient $perhitungan;

    private User $siswa;

    private TingkatSeleksi $tingkat;

    private TingkatSeleksi $provinsi;

    private Simulasi $simulasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->perhitungan = new FakePerhitunganClient;
        $this->app->instance(PerhitunganClientInterface::class, $this->perhitungan);

        $this->siswa = User::factory()->create();
        $this->siswa->assignRole('siswa');

        $this->tingkat = TingkatSeleksi::factory()->create(['urutan' => 1]);
        $this->provinsi = TingkatSeleksi::factory()->create(['urutan' => 2]);

        foreach ([$this->tingkat, $this->provinsi] as $tingkat) {
            $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $tingkat->id]);
            Materi::factory()->create(['tingkat_id' => $tingkat->id, 'kompetensi_id' => $kompetensi->id]);
        }

        $this->simulasi = Simulasi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'is_aktif' => true,
        ]);
    }

    /**
     * Siswa memenuhi syarat: putaran aktif + dua materi wajib selesai dan
     * latihannya mencapai batas. Nilai 80 >= latihan_min_nilai 50.
     */
    private function siapkanSyarat(): Pretest
    {
        $pretest = Pretest::factory()->create([
            'user_id' => $this->siswa->id,
            'tingkat_id' => $this->tingkat->id,
            'nilai' => 60.0,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);

        $materi = Materi::query()->where('tingkat_id', $this->tingkat->id)->get();

        foreach ($materi as $urutan => $satu) {
            RekomendasiMateri::create([
                'pretest_id' => $pretest->id,
                'user_id' => $this->siswa->id,
                'materi_id' => $satu->id,
                'prioritas' => $urutan + 1,
            ]);

            $quiz = Quiz::create([
                'materi_id' => $satu->id,
                'nama_quiz' => 'Quiz '.$satu->judul,
                'jumlah_soal' => 10,
            ]);

            QuizPengerjaan::create([
                'user_id' => $this->siswa->id,
                'quiz_id' => $quiz->id,
                'nilai' => 80.0,
                'disubmit_pada' => now(),
                'selesai_pada' => now(),
            ]);

            ProgressBelajar::create([
                'user_id' => $this->siswa->id,
                'materi_id' => $satu->id,
                'status' => 'selesai',
                'persentase' => 100,
                'tanggal_selesai' => now(),
            ]);
        }

        return $pretest;
    }

    private function isiBankSimulasi(int $mudah, int $sedang, int $sulit): void
    {
        $materiId = Materi::query()->where('tingkat_id', $this->tingkat->id)->value('id');

        foreach (['mudah' => $mudah, 'sedang' => $sedang, 'sulit' => $sulit] as $level => $banyak) {
            Soal::factory()->count($banyak)->create([
                'tingkat_id' => $this->tingkat->id,
                'materi_id' => $materiId,
                'peruntukan' => 'simulasi',
                'level' => $level,
            ]);
        }
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/simulasi')->assertUnauthorized();
    }

    public function test_tingkat_terkunci_dibalas_403(): void
    {
        $this->tingkat->update(['urutan' => 3]);

        $this->actingAs($this->siswa)
            ->getJson('/api/simulasi?tingkat_id='.$this->tingkat->id)
            ->assertForbidden();
    }

    public function test_mulai_tanpa_putaran_aktif_dibalas_409(): void
    {
        $response = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai');

        $response->assertStatus(409);
        $this->assertSame('SYARAT_SIMULASI_BELUM_TERPENUHI', $response->json('kode'));
    }

    public function test_simulasi_nonaktif_tidak_ditemukan(): void
    {
        $this->siapkanSyarat();
        $this->simulasi->update(['is_aktif' => false]);

        $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertNotFound();
    }

    public function test_mulai_mengembalikan_soal_dan_batas_waktu(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $respons = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->assertJsonPath('data.simulasi_id', $this->simulasi->id);

        $soal = $respons->json('data.soal');

        $this->assertCount(10, $soal);
        $this->assertArrayNotHasKey('kunci_jawaban', $soal[0]);
        $this->assertNotNull($respons->json('data.batas_pada'));
    }

    public function test_mulai_dua_kali_melanjutkan_percobaan_yang_sama(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $pertama = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $kedua = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertOk()
            ->json('data.id');

        $this->assertSame($pertama, $kedua);
        $this->assertDatabaseCount('hasil_simulasi', 1);
    }

    public function test_percobaan_kedua_memprioritaskan_soal_baru(): void
    {
        $this->siapkanSyarat();

        // Bank cukup untuk enam putaran; percobaan kedua harus mengambil
        // sepuluh soal baru, bukan mengulang sepuluh soal pertama, karena
        // picker memprioritaskan soal yang tidak ada di daftar dihindari.
        $this->isiBankSimulasi(20, 20, 20);

        // Gagal supaya putaran berlanjut ke percobaan berikutnya.
        $this->perhitungan->nilai = 30.0;

        $pertama = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.soal');

        $idPertama = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->json('data.id');

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$idPertama.'/submit')
            ->assertOk();

        $kedua = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.soal');

        $idsPertama = array_column($pertama, 'id');
        $idsKedua = array_column($kedua, 'id');

        $this->assertSame([], array_intersect($idsPertama, $idsKedua));
    }

    public function test_bank_lebih_kecil_dari_satu_percobaan_dibalas_503(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(2, 2, 1);

        $response = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai');

        $response->assertStatus(503);
        $this->assertSame('BANK_SOAL_TIDAK_CUKUP', $response->json('kode'));
    }

    public function test_submit_saat_kuota_habis_dibalas_409(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(40, 40, 40);

        // Gagal supaya putaran berlanjut sampai kuota habis.
        $this->perhitungan->nilai = 30.0;

        foreach (range(1, 3) as $nomor) {
            $hasilId = $this->actingAs($this->siswa)
                ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
                ->assertCreated()
                ->json('data.id');

            $this->actingAs($this->siswa)
                ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
                ->assertOk();
        }

        $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertStatus(409)
            ->assertJsonPath('kode', 'KUOTA_SIMULASI_HABIS');
    }

    public function test_lulus_menulis_kenaikan_dan_membuka_tingkat_berikutnya(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $this->perhitungan->nilai = 85.0;

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertOk()
            ->assertJsonPath('data.lulus', true);

        $this->assertDatabaseHas('kenaikan_tingkat', [
            'user_id' => $this->siswa->id,
            'tingkat_asal_id' => $this->tingkat->id,
            'tingkat_tujuan_id' => $this->provinsi->id,
            'status' => 'lulus',
        ]);

        $this->actingAs($this->siswa)
            ->getJson('/api/tingkat')
            ->assertOk()
            ->assertJsonPath('data.1.tingkat_terbuka', true);
    }

    public function test_gagal_ketiga_menghapus_data_dan_pre_test_baru_boleh(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(40, 40, 40);

        $this->perhitungan->nilai = 30.0;

        foreach (range(1, 3) as $nomor) {
            $hasilId = $this->actingAs($this->siswa)
                ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
                ->assertCreated()
                ->json('data.id');

            $this->actingAs($this->siswa)
                ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
                ->assertOk()
                ->assertJsonPath('data.lulus', false);
        }

        $this->assertDatabaseHas('kenaikan_tingkat', [
            'user_id' => $this->siswa->id,
            'tingkat_asal_id' => $this->tingkat->id,
            'status' => 'tidak_lulus',
        ]);

        $this->assertDatabaseMissing('progress_belajar', ['user_id' => $this->siswa->id]);
        $this->assertDatabaseMissing('quiz_pengerjaan', ['user_id' => $this->siswa->id]);

        // Pre-test, pemetaan, dan hasil simulasi lama TIDAK dihapus.
        $this->assertDatabaseHas('pretest', ['user_id' => $this->siswa->id]);
        $this->assertSame(3, HasilSimulasi::count());

        // Putaran habis: pre-test baru boleh.
        $this->actingAs($this->siswa)
            ->getJson('/api/tingkat')
            ->assertOk()
            ->assertJsonPath('data.0.tahap', 'PUTARAN_HABIS');
    }

    public function test_simpan_jawaban_lewat_batas_dibalas_409(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        HasilSimulasi::where('id', $hasilId)->update([
            'batas_pada' => now()->subMinutes(5),
        ]);

        $soalId = HasilSimulasiJawaban::where('hasil_simulasi_id', $hasilId)
            ->firstOrFail()->soal_id;

        $response = $this->actingAs($this->siswa)
            ->putJson("/api/hasil-simulasi/{$hasilId}/jawaban", [
                'soal_id' => $soalId,
                'jawaban_user' => 'Coba',
            ]);

        $response->assertStatus(409);
        $this->assertSame('WAKTU_HABIS', $response->json('kode'));
    }

    public function test_review_hanya_setelah_dinilai(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        // Belum selesai: review ditolak dengan kode bisnisnya.
        $this->actingAs($this->siswa)
            ->getJson('/api/hasil-simulasi/'.$hasilId.'/review')
            ->assertStatus(409)
            ->assertJsonPath('kode', 'SIMULASI_BELUM_DINILAI');

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertOk();

        // Selesai: review memuat kunci dan pembahasan.
        $soal = $this->actingAs($this->siswa)
            ->getJson('/api/hasil-simulasi/'.$hasilId.'/review')
            ->assertOk()
            ->json('data.soal');

        $this->assertArrayHasKey('kunci_jawaban', $soal[0]['soal']);
        $this->assertArrayHasKey('status_benar', $soal[0]);
    }

    public function test_penjadwal_menutup_percobaan_kedaluwarsa(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        HasilSimulasi::where('id', $hasilId)->update([
            'batas_pada' => now()->subMinutes(10),
        ]);

        $this->artisan('simulasi:tutup-kedaluwarsa')
            ->assertSuccessful();

        $this->assertDatabaseHas('hasil_simulasi', [
            'id' => $hasilId,
        ]);

        $hasil = HasilSimulasi::findOrFail($hasilId);

        $this->assertNotNull($hasil->disubmit_pada);
        $this->assertNotNull($hasil->selesai_pada);
        $this->assertNotNull($hasil->nilai);
    }

    public function test_nilai_via_job_tetap_memicu_kelulusan(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        Queue::fake();

        $this->perhitungan->gagalDengan = 503;

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertStatus(503);

        Queue::assertPushed(NilaiUlangJob::class, function ($job) use ($hasilId): bool {
            return $job->jenis === JenisPengerjaan::Simulasi && $job->id === $hasilId;
        });

        // Python pulih, job menyelesaikan penilaian beserta kelulusannya.
        $this->perhitungan->gagalDengan = null;
        $this->perhitungan->nilai = 90.0;

        (new NilaiUlangJob(JenisPengerjaan::Simulasi, $hasilId))->handle(
            $this->app->make(PretestServiceInterface::class),
            $this->app->make(LatihanServiceInterface::class),
            $this->app->make(SimulasiServiceInterface::class),
        );

        $this->assertDatabaseHas('kenaikan_tingkat', [
            'user_id' => $this->siswa->id,
            'status' => 'lulus',
        ]);
    }

    public function test_python_gagal_submit_tetap_terkunci(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        Queue::fake();

        $this->perhitungan->gagalDengan = 503;

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertStatus(503);

        $hasil = HasilSimulasi::findOrFail($hasilId);

        $this->assertNotNull($hasil->disubmit_pada);
        $this->assertNull($hasil->selesai_pada);
    }

    public function test_simpan_jawaban_setelah_submit_dibalas_409(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $soalId = HasilSimulasiJawaban::where('hasil_simulasi_id', $hasilId)->firstOrFail()->soal_id;

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertOk();

        $this->actingAs($this->siswa)
            ->putJson("/api/hasil-simulasi/{$hasilId}/jawaban", [
                'soal_id' => $soalId,
                'jawaban_user' => 'A',
            ])
            ->assertStatus(409)
            ->assertJsonPath('kode', 'SUDAH_DISUBMIT');
    }

    public function test_penilaian_yang_kalah_balapan_tidak_mengevaluasi_kelulusan_lagi(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        // Nilai 90 akan menulis kenaikan tingkat bila penilai ini dibiarkan
        // jalan. Penilai lain sudah menutup percobaan lebih dulu dengan
        // nilai 11, jadi yang ini harus berhenti tanpa menulis apa pun.
        $this->perhitungan->nilai = 90.0;
        $this->perhitungan->saatDipanggil = function () use ($hasilId): void {
            HasilSimulasi::where('id', $hasilId)->update([
                'nilai' => 11,
                'lulus' => false,
                'selesai_pada' => now(),
            ]);
        };

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertOk();

        $hasil = HasilSimulasi::findOrFail($hasilId);

        $this->assertSame(11.0, (float) $hasil->nilai);
        $this->assertFalse((bool) $hasil->lulus);
        $this->assertDatabaseCount('kenaikan_tingkat', 0);
    }

    public function test_soal_yang_dihapus_admin_tidak_menggagalkan_submit_dan_review(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $soalId = HasilSimulasiJawaban::where('hasil_simulasi_id', $hasilId)->firstOrFail()->soal_id;
        Soal::findOrFail($soalId)->delete();

        $this->actingAs($this->siswa)
            ->getJson('/api/hasil-simulasi/'.$hasilId)
            ->assertOk();

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertOk();

        $this->actingAs($this->siswa)
            ->getJson('/api/hasil-simulasi/'.$hasilId.'/review')
            ->assertOk();
    }

    private function mulaiLaluLewatkanBatas(): int
    {
        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        HasilSimulasi::where('id', $hasilId)->update(['batas_pada' => now()->subMinutes(10)]);

        return $hasilId;
    }

    public function test_mulai_setelah_batas_lewat_menutup_percobaan_lama_dan_membuat_yang_baru(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(12, 10, 8);

        $lama = $this->mulaiLaluLewatkanBatas();

        // Nilai 10 belum lulus, jadi percobaan kedua masih boleh.
        $this->perhitungan->nilai = 10.0;

        $baru = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $this->assertNotSame($lama, $baru);

        $tertutup = HasilSimulasi::findOrFail($lama);

        $this->assertNotNull($tertutup->disubmit_pada, 'Penutupan tidak boleh ikut di-rollback.');
        $this->assertNotNull($tertutup->selesai_pada);
        $this->assertSame(10.0, (float) $tertutup->nilai);
        $this->assertFalse((bool) $tertutup->lulus);
    }

    public function test_mulai_setelah_batas_lewat_yang_ternyata_lulus_dibalas_sudah_lulus(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $lama = $this->mulaiLaluLewatkanBatas();

        $this->perhitungan->nilai = 95.0;

        $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertStatus(409)
            ->assertJsonPath('kode', 'SUDAH_LULUS');

        // Nilai dan kelulusan tetap tersimpan walau permintaan mulai ditolak.
        $this->assertTrue((bool) HasilSimulasi::findOrFail($lama)->lulus);
        $this->assertDatabaseHas('kenaikan_tingkat', [
            'user_id' => $this->siswa->id,
            'status' => 'lulus',
        ]);
    }

    public function test_mulai_setelah_batas_lewat_saat_python_gagal_mengunci_dan_mengirim_job(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $lama = $this->mulaiLaluLewatkanBatas();

        Queue::fake();
        $this->perhitungan->gagalDengan = 503;

        $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertStatus(503)
            ->assertJsonPath('kode', 'HASIL_SEDANG_DIPROSES');

        Queue::assertPushed(NilaiUlangJob::class, fn ($job): bool => $job->id === $lama);

        $hasil = HasilSimulasi::findOrFail($lama);
        $this->assertNotNull($hasil->disubmit_pada);
        $this->assertNull($hasil->selesai_pada);

        // Selama belum dinilai, mulai lagi ditolak tanpa memanggil Python.
        $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertStatus(409)
            ->assertJsonPath('kode', 'SIMULASI_BELUM_DINILAI');

        $this->assertCount(1, $this->perhitungan->permintaanPenilaian);
    }

    public function test_mulai_setelah_lulus_dibalas_409(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        KenaikanTingkat::create([
            'user_id' => $this->siswa->id,
            'tingkat_asal_id' => $this->tingkat->id,
            'tingkat_tujuan_id' => $this->provinsi->id,
            'status' => 'lulus',
        ]);

        $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertStatus(409)
            ->assertJsonPath('kode', 'SUDAH_LULUS');
    }

    public function test_syarat_belum_terpenuhi_dibalas_409_beserta_rincian(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        ProgressBelajar::where('user_id', $this->siswa->id)->delete();

        $response = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertStatus(409)
            ->assertJsonPath('kode', 'SYARAT_SIMULASI_BELUM_TERPENUHI');

        $detail = json_decode((string) $response->json('detail'), true);

        $this->assertIsString($detail['pesan']);
        $this->assertNotEmpty($detail['rincian']);
        $this->assertFalse($detail['rincian'][0]['selesai']);
        $this->assertArrayHasKey('nilai_latihan', $detail['rincian'][0]);
    }

    public function test_percobaan_milik_siswa_lain_tidak_ditemukan(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $lain = User::factory()->create();
        $lain->assignRole('siswa');

        $this->actingAs($lain)->getJson('/api/hasil-simulasi/'.$hasilId)->assertNotFound();
        $this->actingAs($lain)->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')->assertNotFound();
        $this->actingAs($lain)->getJson('/api/hasil-simulasi/'.$hasilId.'/review')->assertNotFound();
        $this->actingAs($lain)->putJson('/api/hasil-simulasi/'.$hasilId.'/jawaban', [
            'soal_id' => 1,
            'jawaban_user' => 'A',
        ])->assertNotFound();
    }

    public function test_daftar_tanpa_tingkat_dan_tanpa_tingkat_aktif_dibalas_422(): void
    {
        $this->actingAs($this->siswa)
            ->getJson('/api/simulasi')
            ->assertStatus(422)
            ->assertJsonValidationErrors('tingkat_id');

        $this->actingAs($this->siswa)
            ->getJson('/api/simulasi?tingkat_id=abc')
            ->assertStatus(422)
            ->assertJsonValidationErrors('tingkat_id');
    }

    public function test_penjadwal_tetap_menutup_baris_lain_saat_satu_baris_gagal(): void
    {
        $this->siapkanSyarat();
        $this->isiBankSimulasi(6, 5, 4);
        $this->mulaiLaluLewatkanBatas();

        // Siswa kedua juga punya percobaan kedaluwarsa.
        $siswaPertama = $this->siswa;
        $this->siswa = User::factory()->create();
        $this->siswa->assignRole('siswa');
        $pretestKedua = Pretest::factory()->create([
            'user_id' => $this->siswa->id,
            'tingkat_id' => $this->tingkat->id,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);
        HasilSimulasi::factory()->create([
            'user_id' => $this->siswa->id,
            'simulasi_id' => $this->simulasi->id,
            'pretest_id' => $pretestKedua->id,
            'batas_pada' => now()->subMinutes(10),
            'disubmit_pada' => null,
            'selesai_pada' => null,
            'nilai' => null,
        ]);
        $this->siswa = $siswaPertama;

        // Layanan hitung salah konfigurasi (502): bukan kegagalan sementara.
        $this->perhitungan->lempar = new PerhitunganKonfigurasiException('Token ditolak.');

        $this->artisan('simulasi:tutup-kedaluwarsa')->assertSuccessful();

        // Kedua baris dicoba; baris pertama yang gagal tidak menghentikan
        // baris kedua.
        $this->assertCount(2, $this->perhitungan->permintaanPenilaian);
        $this->assertSame(2, HasilSimulasi::whereNotNull('disubmit_pada')->count());
    }
}
