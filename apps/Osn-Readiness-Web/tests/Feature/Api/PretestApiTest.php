<?php

namespace Tests\Feature\Api;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Enums\JenisPengerjaan;
use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Enums\StatusKenaikan;
use App\Enums\TahapSiswa;
use App\Exceptions\PerhitunganTidakTersediaException;
use App\Jobs\NilaiUlangJob;
use App\Models\HasilSimulasi;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\AturanPemetaanSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TingkatSeleksiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakePerhitunganClient;
use Tests\TestCase;

class PretestApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private FakePerhitunganClient $perhitungan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            TingkatSeleksiSeeder::class,
            AturanPemetaanSeeder::class,
        ]);

        $this->perhitungan = new FakePerhitunganClient;
        $this->app->instance(PerhitunganClientInterface::class, $this->perhitungan);
    }

    private function siswa(): User
    {
        return User::factory()->create(['tingkat_aktif_id' => null]);
    }

    /**
     * Bank soal yang cukup untuk tiga putaran: 5 materi, tiap level 40/30/20.
     *
     * @return array<int, Materi>
     */
    private function bank(int $jumlahMateri = 5, int $urutanTingkat = 1): array
    {
        $tingkat = TingkatSeleksi::where('urutan', $urutanTingkat)->firstOrFail();

        // Kompetensi dan materi harus satu tingkat; factory bawaan membuat
        // tingkat sendiri untuk tiap baris.
        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $tingkat->id]);

        $materi = [];

        for ($i = 1; $i <= $jumlahMateri; $i++) {
            $materi[] = Materi::factory()->create([
                'tingkat_id' => $tingkat->id,
                'kompetensi_id' => $kompetensi->id,
                'urutan' => $i,
            ]);
        }

        foreach ($materi as $satu) {
            foreach (['mudah' => 40, 'sedang' => 30, 'sulit' => 20] as $level => $jumlah) {
                Soal::factory()
                    ->count($jumlah)
                    ->untukMateri($satu)
                    ->level(Level::from($level))
                    ->peruntukan(Peruntukan::Pretest)
                    ->create();
            }
        }

        return $materi;
    }

    private function mulai(User $user, ?int $tingkatId = null): TestResponse
    {
        return $this->actingAs($user)->postJson('/api/pretest', [
            'tingkat_id' => $tingkatId ?? TingkatSeleksi::where('urutan', 1)->firstOrFail()->id,
        ]);
    }

    #[Test]
    public function tanpa_token_mengalas_401(): void
    {
        $this->postJson('/api/pretest', ['tingkat_id' => 1])->assertUnauthorized();
    }

    #[Test]
    public function tingkat_id_wajib_dan_must_exist(): void
    {
        $user = $this->siswa();

        $this->actingAs($user)->postJson('/api/pretest', [])
            ->assertStatus(422)->assertJsonValidationErrors(['tingkat_id']);

        $this->actingAs($user)->postJson('/api/pretest', ['tingkat_id' => 9999])
            ->assertStatus(422)->assertJsonValidationErrors(['tingkat_id']);
    }

    #[Test]
    public function tingkat_terkunci_mengalas_403(): void
    {
        $user = $this->siswa();
        $provinsi = TingkatSeleksi::where('urutan', 2)->firstOrFail();
        $this->bank();

        $this->mulai($user, $provinsi->id)
            ->assertForbidden()
            ->assertJsonPath('kode', 'TINGKAT_TERKUNCI');
    }

    #[Test]
    public function tingkat_yang_sudah_lulus_mengalas_409(): void
    {
        $user = $this->siswa();
        $kabupaten = TingkatSeleksi::where('urutan', 1)->firstOrFail();
        $this->bank();

        $user->kenaikanTingkat()->create([
            'tingkat_asal_id' => $kabupaten->id,
            'status' => StatusKenaikan::Lulus,
        ]);

        $this->mulai($user, $kabupaten->id)
            ->assertStatus(409)
            ->assertJsonPath('kode', 'SUDAH_LULUS');
    }

    #[Test]
    public function mulai_membuat_pretest_dengan_30_soal_dan_mengisi_tingkat_aktif(): void
    {
        $user = $this->siswa();
        $kabupaten = TingkatSeleksi::where('urutan', 1)->firstOrFail();
        $this->bank();

        $response = $this->mulai($user);

        $response->assertCreated()
            ->assertJsonPath('data.tingkat_id', $kabupaten->id)
            ->assertJsonPath('data.selesai_pada', null)
            ->assertJsonCount(30, 'data.soal');

        // Kunci jawaban tidak boleh ikut.
        $this->assertStringNotContainsString('kunci_jawaban', $response->getContent());

        $this->assertSame(1, Pretest::count());
        $this->assertSame(30, $user->fresh()->pretest()->first()->jawaban()->count());
        $this->assertSame($kabupaten->id, $user->fresh()->tingkat_aktif_id);
    }

    #[Test]
    public function soal_berikutnya_mengikuti_kuota_level_dan_batas_materi(): void
    {
        $user = $this->siswa();
        $this->bank();

        $soal = $this->mulai($user)->json('data.soal');
        $level = array_count_values(array_column($soal, 'level'));

        $this->assertSame(15, $level['mudah']);
        $this->assertSame(9, $level['sedang']);
        $this->assertSame(6, $level['sulit']);

        $perMateri = array_count_values(array_column($soal, 'materi_id'));

        foreach ($perMateri as $jumlah) {
            $this->assertGreaterThanOrEqual(2, $jumlah);
        }
    }

    #[Test]
    public function pretest_yang_sedang_berjalan_dikembalikan_ulang_dengan_200(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pertama = $this->mulai($user)->assertCreated();
        $kedua = $this->mulai($user)->assertOk();

        $this->assertSame(
            $pertama->json('data.id'),
            $kedua->json('data.id'),
            'Panggilan kedua harus mengembalikan pre-test yang sama.',
        );
        $this->assertSame(1, Pretest::count());
    }

    #[Test]
    public function simpan_jawaban_tersimpan_dan_muncul_di_soal(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');
        $soalId = $this->actingAs($user)->getJson("/api/pretest/{$pretestId}")
            ->json('data.soal.0.id');

        $this->actingAs($user)->putJson("/api/pretest/{$pretestId}/jawaban", [
            'soal_id' => $soalId,
            'jawaban_user' => 'B',
        ])->assertOk();

        $this->actingAs($user)->getJson("/api/pretest/{$pretestId}")
            ->assertJsonPath('data.soal.0.jawaban_user', 'B');
    }

    #[Test]
    public function simpan_jawaban_boleh_kosong(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');
        $soalId = $this->actingAs($user)->getJson("/api/pretest/{$pretestId}")
            ->json('data.soal.0.id');

        $this->actingAs($user)->putJson("/api/pretest/{$pretestId}/jawaban", [
            'soal_id' => $soalId,
            'jawaban_user' => null,
        ])->assertOk();
    }

    #[Test]
    public function simpan_jawaban_soal_asing_mengalas_422(): void
    {
        $user = $this->siswa();
        $materi = $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');

        // Soal dari materi lain, tidak pernah masuk pre-test ini.
        $asing = Soal::factory()->untukMateri($materi[0])->peruntukan(Peruntukan::Pretest)->create();

        $this->actingAs($user)->putJson("/api/pretest/{$pretestId}/jawaban", [
            'soal_id' => $asing->id,
            'jawaban_user' => 'A',
        ])->assertStatus(422)->assertJsonValidationErrors(['soal_id']);
    }

    #[Test]
    public function pretest_milik_orang_lain_mengalas_404(): void
    {
        $pemilik = $this->siswa();
        $asing = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($pemilik)->json('data.id');

        foreach ([
            ['getJson', "/api/pretest/{$pretestId}"],
            ['putJson', "/api/pretest/{$pretestId}/jawaban"],
            ['postJson', "/api/pretest/{$pretestId}/submit"],
        ] as [$metode, $url]) {
            $this->actingAs($asing)->{$metode}($url, $metode === 'putJson'
                ? ['soal_id' => 1, 'jawaban_user' => 'A']
                : [])->assertNotFound();
        }
    }

    #[Test]
    public function simpan_jawaban_setelah_submit_mengalas_409(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');
        $soalId = $this->actingAs($user)->getJson("/api/pretest/{$pretestId}")
            ->json('data.soal.0.id');

        $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit")->assertOk();

        $this->actingAs($user)->putJson("/api/pretest/{$pretestId}/jawaban", [
            'soal_id' => $soalId,
            'jawaban_user' => 'A',
        ])->assertStatus(409)->assertJsonPath('kode', 'SUDAH_DISUBMIT');
    }

    #[Test]
    public function submit_menilai_menyimpan_pemetaan_dan_materi_wajib(): void
    {
        $user = $this->siswa();
        $materi = $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');

        foreach ($this->actingAs($user)->getJson("/api/pretest/{$pretestId}")->json('data.soal') as $soal) {
            $this->actingAs($user)->putJson("/api/pretest/{$pretestId}/jawaban", [
                'soal_id' => $soal['id'],
                'jawaban_user' => 'A',
            ])->assertOk();
        }

        $response = $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'tingkat_id', 'nilai', 'pemetaan', 'materi_wajib'],
            ]);

        $this->perhitungan->nilai = 82.5;

        $pretest = Pretest::findOrFail($pretestId);
        $this->assertNotNull($pretest->nilai);
        $this->assertNotNull($pretest->disubmit_pada);
        $this->assertNotNull($pretest->selesai_pada);
        $this->assertCount(30, $pretest->jawaban);
        $this->assertNotNull($pretest->jawaban->first()->status_benar);
        $this->assertCount(5, $pretest->pemetaanMateri);
        $this->assertCount(3, $pretest->rekomendasiMateri);
    }

    #[Test]
    public function submit_kirim_payload_lengkap_ke_python(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');
        $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit")->assertOk();

        $this->assertCount(1, $this->perhitungan->permintaanPretest);

        $permintaan = $this->perhitungan->permintaanPretest[0];

        $this->assertCount(30, $permintaan['soal']);
        $this->assertCount(5, $permintaan['materi']);
        $this->assertSame(3, $permintaan['jumlah_materi_wajib']);

        // Tiap butir soal harus punya kunci dan materi.
        foreach ($permintaan['soal'] as $butir) {
            $this->assertArrayHasKey('kunci_jawaban', $butir);
            $this->assertArrayHasKey('materi_id', $butir);
            $this->assertArrayHasKey('bobot', $butir);
            $this->assertArrayHasKey('tipe_soal', $butir);
        }
    }

    #[Test]
    public function python_gagal_mengunci_jawaban_mengirim_job_dan_mengalas_503(): void
    {
        Queue::fake();

        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');

        $this->perhitungan->gagalDengan = 503;

        $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit")
            ->assertStatus(503)
            ->assertJsonPath('kode', 'HASIL_SEDANG_DIPROSES')
            ->assertJsonPath('message', 'Layanan hitung sedang tidak tersedia.')
            ->assertJsonPath('detail', "Penilaian pre-test #{$pretestId} gagal dan dijadwalkan untuk dicoba lagi.");

        Queue::assertPushed(NilaiUlangJob::class);

        $pretest = Pretest::findOrFail($pretestId);
        $this->assertNotNull($pretest->disubmit_pada, 'Jawaban harus terkunci walau Python gagal.');
        $this->assertNull($pretest->selesai_pada);
        $this->assertNull($pretest->nilai);
    }

    #[Test]
    public function submit_dua_kali_tidak_menilai_ulang(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');

        $pertama = $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit")->assertOk();
        $kedua = $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit")->assertOk();

        $this->assertSame($pertama->json('data.nilai'), $kedua->json('data.nilai'));

        // Python hanya dipanggil sekali.
        $this->assertCount(1, $this->perhitungan->permintaanPretest);
        $this->assertSame(5, $pretestId ? Pretest::findOrFail($pretestId)->pemetaanMateri()->count() : 0);
    }

    #[Test]
    public function job_nilai_ulang_menyelesaikan_penilaian_setelah_python_pulih(): void
    {
        // Queue tidak di-fake: dispatch dari submit memakai koneksi sync, jadi
        // job langsung jalan di dalam test. Itu justru yang perlu dibuktikan —
        // job yang gagal tidak boleh memanggil dirinya lagi.
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');
        $this->perhitungan->gagalDengan = 503;

        $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit")->assertStatus(503);
        $this->assertNull(Pretest::findOrFail($pretestId)->selesai_pada);

        // Python kembali sehat.
        $this->perhitungan->gagalDengan = null;

        NilaiUlangJob::dispatchSync(JenisPengerjaan::Pretest, $pretestId);

        $pretest = Pretest::findOrFail($pretestId);
        $this->assertNotNull($pretest->selesai_pada);
        $this->assertNotNull($pretest->nilai);
        $this->assertCount(5, $pretest->pemetaanMateri);
    }

    #[Test]
    public function job_yang_gagal_tidak_mengirim_job_lagi(): void
    {
        // Kalau selesaikanPenilaian ikut mengirim NilaiUlangJob, pemanggilan
        // dari job akan mengirim job lagi selamanya. Yang mengirimi job hanya
        // submit(); retry-nya milik job itu sendiri.
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');
        $this->perhitungan->gagalDengan = 503;

        // Kalau selesaikanPenilaian ikut mengirim job, pemanggilan dari job
        // akan mengirim job tanpa henti. Kalau tidak, job Rethrow exception
        // supaya retry-nya yang mengatur.
        $this->expectException(PerhitunganTidakTersediaException::class);

        NilaiUlangJob::dispatchSync(JenisPengerjaan::Pretest, $pretestId);
    }

    #[Test]
    public function get_setelah_selesai_menampilkan_hasil_bukan_soal(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');
        $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit")->assertOk();

        $this->actingAs($user)->getJson("/api/pretest/{$pretestId}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['nilai', 'pemetaan', 'materi_wajib']])
            ->assertJsonMissingPath('data.soal');
    }

    #[Test]
    public function pretest_baru_tidak_boleh_while_putaran_aktif(): void
    {
        // Satu putaran masih berjalan, jadi pre-test berikutnya ditolak.
        $user = $this->siswa();
        $this->bank();

        $pertama = $this->mulai($user)->assertCreated();
        $this->actingAs($user)->postJson("/api/pretest/{$pertama->json('data.id')}/submit")->assertOk();

        $this->mulai($user)
            ->assertStatus(409)
            ->assertJsonPath('kode', 'PUTARAN_MASIH_BERJALAN');
    }

    #[Test]
    public function pretest_kedua_setelah_putaran_habis_tidak_mengulang_soal_pertama(): void
    {
        $user = $this->siswa();
        $this->bank();
        $kabupaten = TingkatSeleksi::where('urutan', 1)->firstOrFail();
        $simulasi = Simulasi::factory()->create(['tingkat_id' => $kabupaten->id]);

        $pertama = $this->mulai($user)->assertCreated();
        $soalPertama = array_column($pertama->json('data.soal'), 'id');
        $pretestPertama = $pertama->json('data.id');

        $this->actingAs($user)->postJson("/api/pretest/{$pretestPertama}/submit")->assertOk();

        // Tiga percobaan simulasi gagal memakai pre-test yang sama.
        for ($i = 0; $i < 3; $i++) {
            HasilSimulasi::factory()->selesai(40.0, lulus: false)->create([
                'user_id' => $user->id,
                'pretest_id' => $pretestPertama,
                'simulasi_id' => $simulasi->id,
            ]);
        }

        $kedua = $this->mulai($user)->assertCreated();
        $soalKedua = array_column($kedua->json('data.soal'), 'id');

        $this->assertCount(30, $soalKedua);
        $this->assertNotSame($pretestPertama, $kedua->json('data.id'));
        $this->assertEmpty(
            array_intersect($soalPertama, $soalKedua),
            'Pre-test kedua memakai soal dari pre-test pertama.',
        );
    }

    #[Test]
    public function pretest_kedua_terbuka_setelah_putaran_pertama_selesai(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pertama = $this->mulai($user)->assertCreated();
        $this->actingAs($user)->postJson("/api/pretest/{$pertama->json('data.id')}/submit")->assertOk();

        $status = $this->actingAs($user)->getJson('/api/tingkat');

        $status->assertOk()
            ->assertJsonPath('data.0.tahap', TahapSiswa::Belajar->value);
    }

    #[Test]
    public function bank_soal_kurang_mengalas_503(): void
    {
        $user = $this->siswa();
        // Bank cuma satu soal per level, tidak mungkin menyusun 30 soal.
        $kabupaten = TingkatSeleksi::where('urutan', 1)->firstOrFail();
        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $kabupaten->id]);
        $materi = Materi::factory()->create([
            'tingkat_id' => $kabupaten->id,
            'kompetensi_id' => $kompetensi->id,
            'urutan' => 1,
        ]);

        foreach (Level::cases() as $level) {
            Soal::factory()->count(2)->untukMateri($materi)->level($level)->peruntukan(Peruntukan::Pretest)->create();
        }

        Log::spy();

        $this->mulai($user)
            ->assertStatus(503)
            ->assertJsonPath('kode', 'BANK_SOAL_TIDAK_CUKUP');

        // Rincian kekurangan dicatat untuk admin (BE-03), sebagai peringatan
        // dan bukan error ber-stack-trace.
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $pesan, array $konteks): bool => $konteks['kode'] === 'BANK_SOAL_TIDAK_CUKUP'
                && is_array($konteks['detail']))
            ->once();
        Log::shouldNotHaveReceived('error');

        $this->assertSame(0, Pretest::count());
    }

    #[Test]
    public function penilaian_yang_kalah_balapan_tidak_menimpa_hasil(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');

        // Saat Python masih menghitung, penilai lain (job atau submit ulang)
        // sudah menyelesaikan pre-test ini lebih dulu.
        $this->perhitungan->saatDipanggil = function () use ($pretestId): void {
            Pretest::where('id', $pretestId)->update(['nilai' => 11, 'selesai_pada' => now()]);
        };

        $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit")->assertOk();

        $pretest = Pretest::findOrFail($pretestId);

        $this->assertSame(11.0, (float) $pretest->nilai, 'Hasil penilai pertama tidak boleh ditimpa.');
        $this->assertSame(0, $pretest->pemetaanMateri()->count(), 'Penilai yang kalah tidak boleh menulis pemetaan.');
    }

    #[Test]
    public function soal_yang_dihapus_admin_tidak_menggagalkan_pretest_berjalan(): void
    {
        $user = $this->siswa();
        $this->bank();

        $pretestId = $this->mulai($user)->json('data.id');
        $soalId = $this->actingAs($user)->getJson("/api/pretest/{$pretestId}")->json('data.soal.0.id');

        Soal::findOrFail($soalId)->delete();

        $this->actingAs($user)->getJson("/api/pretest/{$pretestId}")
            ->assertOk()
            ->assertJsonCount(30, 'data.soal');

        $this->actingAs($user)->postJson("/api/pretest/{$pretestId}/submit")->assertOk();

        $this->assertNotNull(Pretest::findOrFail($pretestId)->selesai_pada);
        $this->assertCount(30, $this->perhitungan->permintaanPretest[0]['soal']);
    }
}
