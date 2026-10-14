<?php

namespace Tests\Feature\Api;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Contracts\Repositories\QuizPengerjaanRepositoryInterface;
use App\Enums\JenisPengerjaan;
use App\Jobs\NilaiUlangJob;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\Quiz;
use App\Models\QuizJawaban;
use App\Models\QuizPengerjaan;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use App\Repositories\Eloquent\QuizPengerjaanRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakePerhitunganClient;
use Tests\TestCase;

class LatihanApiTest extends TestCase
{
    use RefreshDatabase;

    private FakePerhitunganClient $perhitungan;

    private User $siswa;

    private TingkatSeleksi $tingkat;

    private Materi $materi;

    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->perhitungan = new FakePerhitunganClient;
        $this->app->instance(PerhitunganClientInterface::class, $this->perhitungan);

        $this->siswa = User::factory()->create();
        $this->siswa->assignRole('siswa');

        $this->tingkat = TingkatSeleksi::factory()->create(['urutan' => 1]);
        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $this->tingkat->id]);
        $this->materi = Materi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'kompetensi_id' => $kompetensi->id,
        ]);
        $this->quiz = Quiz::create([
            'materi_id' => $this->materi->id,
            'nama_quiz' => 'Latihan '.$this->materi->judul,
            'jumlah_soal' => 10,
        ]);
    }

    /**
     * Putaran aktif = pretest terakhir siswa di tingkat itu yang sudah
     * selesai dikerjakan.
     */
    private function buatPutaranAktif(): Pretest
    {
        return Pretest::factory()->create([
            'user_id' => $this->siswa->id,
            'tingkat_id' => $this->tingkat->id,
            'nilai' => 60.0,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);
    }

    private function isiBankSoal(int $jumlah): void
    {
        // Latihan diambil tanpa pembagian level, dan SoalPickerService
        // mengambilnya dari bank level mudah, jadi bank latihan per materi
        // dibuat semua mudah.
        $komposisi = [
            'mudah' => $jumlah,
        ];

        foreach ($komposisi as $level => $banyak) {
            Soal::factory()->count($banyak)->create([
                'tingkat_id' => $this->tingkat->id,
                'materi_id' => $this->materi->id,
                'peruntukan' => 'latihan',
                'level' => $level,
            ]);
        }
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->postJson('/api/quiz/'.$this->quiz->id.'/mulai')->assertUnauthorized();
    }

    public function test_tingkat_terkunci_dibalas_403(): void
    {
        // Tingkat urutan dua terkunci selama tingkat pertama belum lulus.
        $this->tingkat->update(['urutan' => 2]);

        $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertForbidden();
    }

    public function test_mulai_tanpa_putaran_aktif_dibalas_409(): void
    {
        $response = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai');

        $response->assertStatus(409);
        $this->assertSame('BELUM_PRETEST', $response->json('kode'));
    }

    public function test_mulai_mengembalikan_soal_sejumlah_konfigurasi_quiz(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $soal = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->assertJsonPath('data.quiz_id', $this->quiz->id)
            ->assertJsonPath('data.materi_id', $this->materi->id)
            ->json('data.soal');

        $this->assertCount(10, $soal);
        $this->assertArrayNotHasKey('kunci_jawaban', $soal[0]);
    }

    public function test_mulai_dua_kali_melanjutkan_pengerjaan_yang_sama(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pertama = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $kedua = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertOk()
            ->json('data.id');

        $this->assertSame($pertama, $kedua);
        $this->assertDatabaseCount('quiz_pengerjaan', 1);
    }

    public function test_simpan_jawaban_tersimpan(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $soalId = QuizJawaban::where('pengerjaan_id', $pengerjaanId)
            ->firstOrFail()->soal_id;

        $this->actingAs($this->siswa)
            ->putJson("/api/quiz-pengerjaan/{$pengerjaanId}/jawaban", [
                'soal_id' => $soalId,
                'jawaban_user' => 'Jawaban A',
            ])
            ->assertOk();

        $this->assertDatabaseHas('quiz_jawaban', [
            'pengerjaan_id' => $pengerjaanId,
            'soal_id' => $soalId,
            'jawaban_user' => 'Jawaban A',
        ]);
    }

    public function test_simpan_jawaban_soal_asing_dibalas_422(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $asing = Soal::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'materi_id' => $this->materi->id,
            'peruntukan' => 'latihan',
        ]);

        $this->actingAs($this->siswa)
            ->putJson("/api/quiz-pengerjaan/{$pengerjaanId}/jawaban", [
                'soal_id' => $asing->id,
                'jawaban_user' => 'Jawaban A',
            ])
            ->assertUnprocessable();
    }

    public function test_pengerjaan_milik_orang_lain_tidak_ditemukan(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $penyusup = User::factory()->create();
        $penyusup->assignRole('siswa');

        $this->actingAs($penyusup)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertNotFound();
    }

    public function test_submit_menghasilkan_nilai_dan_mengunci_jawaban(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $hasil = $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertOk()
            ->assertJsonPath('data.quiz_id', $this->quiz->id);

        $this->assertSame(75.0, (float) $hasil->json('data.nilai'));
        $this->assertNotNull($hasil->json('data.selesai_pada'));

        $jawaban = $hasil->json('data.jawaban');
        $this->assertCount(10, $jawaban);

        foreach ($jawaban as $baris) {
            $this->assertIsBool($baris['status_benar']);
        }
    }

    public function test_lihat_pengerjaan_selesai_membalas_hasil_tanpa_soal(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertOk();

        $this->actingAs($this->siswa)
            ->getJson('/api/quiz-pengerjaan/'.$pengerjaanId)
            ->assertOk()
            ->assertJsonMissingPath('data.soal')
            ->assertJsonPath('data.quiz_id', $this->quiz->id)
            ->assertJsonStructure(['data' => ['id', 'quiz_id', 'materi_id', 'nilai', 'jawaban']]);
    }

    public function test_submit_dua_kali_tidak_menilai_ulang(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $pertama = $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertOk()
            ->json('data');

        $kedua = $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertOk()
            ->json('data');

        $this->assertSame($pertama['selesai_pada'], $kedua['selesai_pada']);
        $this->assertSame($pertama['nilai'], $kedua['nilai']);
        $this->assertSame(1, count($this->perhitungan->permintaanPenilaian));
    }

    public function test_python_gagal_dibalas_503_dan_job_terkirim(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        Queue::fake();

        $this->perhitungan->gagalDengan = 503;

        $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertStatus(503)
            ->assertJsonPath('kode', 'HASIL_SEDANG_DIPROSES');

        Queue::assertPushed(NilaiUlangJob::class, function ($job) use ($pengerjaanId): bool {
            return $job->jenis === JenisPengerjaan::Latihan && $job->id === $pengerjaanId;
        });
    }

    public function test_nilai_terbaik_adalah_maksimum_pengerjaan_selesai(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pertama = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pertama.'/submit')
            ->assertOk();

        // Pengerjaan kedua dipaksa bernilai lebih tinggi lewat DB, karena
        // client palsu selalu menghasilkan nilai yang sama.
        $kedua = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        QuizPengerjaan::where('id', $kedua)->update([
            'nilai' => 95.0,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);

        $respons = $this->actingAs($this->siswa)
            ->getJson('/api/materi?tingkat_id='.$this->tingkat->id)
            ->assertOk();

        $this->assertSame(95.0, (float) $respons->json('data.0.nilai_latihan_terbaik'));
    }

    public function test_simpan_jawaban_setelah_submit_dibalas_409(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $soalId = QuizJawaban::where('pengerjaan_id', $pengerjaanId)->firstOrFail()->soal_id;

        $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertOk();

        $this->actingAs($this->siswa)
            ->putJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/jawaban', [
                'soal_id' => $soalId,
                'jawaban_user' => 'A',
            ])
            ->assertStatus(409)
            ->assertJsonPath('kode', 'SUDAH_DISUBMIT');
    }

    public function test_index_menolak_dua_pengerjaan_berjalan_untuk_latihan_yang_sama(): void
    {
        QuizPengerjaan::create(['user_id' => $this->siswa->id, 'quiz_id' => $this->quiz->id]);

        $this->expectException(UniqueConstraintViolationException::class);

        QuizPengerjaan::create(['user_id' => $this->siswa->id, 'quiz_id' => $this->quiz->id]);
    }

    public function test_pengerjaan_baru_boleh_setelah_yang_lama_disubmit(): void
    {
        QuizPengerjaan::create([
            'user_id' => $this->siswa->id,
            'quiz_id' => $this->quiz->id,
            'disubmit_pada' => now(),
        ]);

        QuizPengerjaan::create(['user_id' => $this->siswa->id, 'quiz_id' => $this->quiz->id]);

        $this->assertDatabaseCount('quiz_pengerjaan', 2);
    }

    public function test_mulai_bersamaan_melanjutkan_pengerjaan_yang_menang(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        // Request lain sudah membuat pengerjaan, tetapi request ini belum
        // melihatnya saat memeriksa: pemeriksaan pertama dibuat meleset.
        $pemenang = QuizPengerjaan::create(['user_id' => $this->siswa->id, 'quiz_id' => $this->quiz->id]);

        $this->app->bind(QuizPengerjaanRepositoryInterface::class, function () {
            return new class(new QuizPengerjaan) extends QuizPengerjaanRepository
            {
                private bool $sudahMeleset = false;

                public function berjalan(User $user, int $quizId): ?QuizPengerjaan
                {
                    if (! $this->sudahMeleset) {
                        $this->sudahMeleset = true;

                        return null;
                    }

                    return parent::berjalan($user, $quizId);
                }
            };
        });

        $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertOk()
            ->assertJsonPath('data.id', $pemenang->id);

        $this->assertDatabaseCount('quiz_pengerjaan', 1);
    }

    public function test_penilaian_yang_kalah_balapan_tidak_menimpa_hasil(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        // Saat Python masih menghitung, penilai lain sudah selesai lebih dulu.
        $this->perhitungan->saatDipanggil = function () use ($pengerjaanId): void {
            QuizPengerjaan::where('id', $pengerjaanId)->update(['nilai' => 11, 'selesai_pada' => now()]);
        };

        $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertOk();

        $this->assertSame(11.0, (float) QuizPengerjaan::findOrFail($pengerjaanId)->nilai);
        $this->assertSame(
            0,
            QuizJawaban::where('pengerjaan_id', $pengerjaanId)->whereNotNull('status_benar')->count(),
            'Penilai yang kalah tidak boleh menulis status jawaban.',
        );
    }

    public function test_soal_yang_dihapus_admin_tidak_menggagalkan_latihan_berjalan(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(15);

        $pengerjaanId = $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $soalId = QuizJawaban::where('pengerjaan_id', $pengerjaanId)->firstOrFail()->soal_id;
        Soal::findOrFail($soalId)->delete();

        $this->actingAs($this->siswa)
            ->getJson('/api/quiz-pengerjaan/'.$pengerjaanId)
            ->assertOk();

        $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertOk()
            ->assertJsonCount(10, 'data.jawaban');
    }
}
