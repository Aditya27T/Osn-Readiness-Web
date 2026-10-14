<?php

namespace Tests\Feature\Api;

use App\Contracts\Clients\PerhitunganClientInterface;
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
use Tests\Fakes\FakePerhitunganClient;
use Tests\TestCase;

/**
 * Menutup dua endpoint baca yang belum punya testcase:
 * GET /api/quiz-pengerjaan/{pengerjaan} dan
 * GET /api/hasil-simulasi/{hasil}.
 */
class RingkasanPengerjaanApiTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private TingkatSeleksi $tingkat;

    private Materi $materi;

    private Quiz $quiz;

    private Simulasi $simulasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->app->instance(PerhitunganClientInterface::class, new FakePerhitunganClient);

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
        $this->simulasi = Simulasi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'is_aktif' => true,
        ]);
    }

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

    private function mulaiLatihan(): int
    {
        $this->buatPutaranAktif();
        Soal::factory()->count(15)->create([
            'tingkat_id' => $this->tingkat->id,
            'materi_id' => $this->materi->id,
            'peruntukan' => 'latihan',
            'level' => 'mudah',
        ]);

        return $this->actingAs($this->siswa)
            ->postJson('/api/quiz/'.$this->quiz->id.'/mulai')
            ->assertCreated()
            ->json('data.id');
    }

    private function mulaiSimulasi(): int
    {
        $pretest = $this->buatPutaranAktif();

        RekomendasiMateri::create([
            'pretest_id' => $pretest->id,
            'user_id' => $this->siswa->id,
            'materi_id' => $this->materi->id,
            'prioritas' => 1,
        ]);
        $quiz = $this->quiz;
        QuizPengerjaan::create([
            'user_id' => $this->siswa->id,
            'quiz_id' => $quiz->id,
            'nilai' => 80.0,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);
        ProgressBelajar::create([
            'user_id' => $this->siswa->id,
            'materi_id' => $this->materi->id,
            'status' => 'selesai',
            'persentase' => 100,
            'tanggal_selesai' => now(),
        ]);

        foreach (['mudah' => 20, 'sedang' => 20, 'sulit' => 20] as $level => $banyak) {
            Soal::factory()->count($banyak)->create([
                'tingkat_id' => $this->tingkat->id,
                'materi_id' => $this->materi->id,
                'peruntukan' => 'simulasi',
                'level' => $level,
            ]);
        }

        return $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$this->simulasi->id.'/mulai')
            ->assertCreated()
            ->json('data.id');
    }

    public function test_quiz_pengerjaan_tanpa_token_dibalas_401(): void
    {
        $this->getJson('/api/quiz-pengerjaan/1')->assertUnauthorized();
    }

    public function test_quiz_pengerjaan_show_mengembalikan_ringkasan_tanpa_kunci(): void
    {
        $pengerjaanId = $this->mulaiLatihan();

        $soal = $this->actingAs($this->siswa)
            ->getJson('/api/quiz-pengerjaan/'.$pengerjaanId)
            ->assertOk()
            ->assertJsonPath('data.id', $pengerjaanId)
            ->assertJsonPath('data.quiz_id', $this->quiz->id)
            ->json('data.soal');

        $this->assertCount(10, $soal);
        $this->assertArrayNotHasKey('kunci_jawaban', $soal[0]);
    }

    public function test_quiz_pengerjaan_milik_siswa_lain_tidak_ditemukan(): void
    {
        $pengerjaanId = $this->mulaiLatihan();

        $lain = User::factory()->create();
        $lain->assignRole('siswa');

        $this->actingAs($lain)
            ->getJson('/api/quiz-pengerjaan/'.$pengerjaanId)
            ->assertNotFound();
    }

    public function test_quiz_pengerjaan_show_setelah_submit_tetap_200(): void
    {
        $pengerjaanId = $this->mulaiLatihan();

        $this->actingAs($this->siswa)
            ->postJson('/api/quiz-pengerjaan/'.$pengerjaanId.'/submit')
            ->assertOk();

        $this->actingAs($this->siswa)
            ->getJson('/api/quiz-pengerjaan/'.$pengerjaanId)
            ->assertOk()
            ->assertJsonPath('data.id', $pengerjaanId);
    }

    public function test_hasil_simulasi_tanpa_token_dibalas_401(): void
    {
        $this->getJson('/api/hasil-simulasi/1')->assertUnauthorized();
    }

    public function test_hasil_simulasi_show_berjalan_mengembalikan_soal_tanpa_kunci(): void
    {
        $hasilId = $this->mulaiSimulasi();

        $soal = $this->actingAs($this->siswa)
            ->getJson('/api/hasil-simulasi/'.$hasilId)
            ->assertOk()
            ->assertJsonPath('data.id', $hasilId)
            ->json('data.soal');

        $this->assertCount(10, $soal);
        $this->assertArrayNotHasKey('kunci_jawaban', $soal[0]);
    }

    public function test_hasil_simulasi_milik_siswa_lain_tidak_ditemukan(): void
    {
        $hasilId = $this->mulaiSimulasi();

        $lain = User::factory()->create();
        $lain->assignRole('siswa');

        $this->actingAs($lain)
            ->getJson('/api/hasil-simulasi/'.$hasilId)
            ->assertNotFound();
    }

    public function test_hasil_simulasi_show_setelah_submit_mengembalikan_nilai(): void
    {
        $hasilId = $this->mulaiSimulasi();

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertOk();

        $response = $this->actingAs($this->siswa)
            ->getJson('/api/hasil-simulasi/'.$hasilId)
            ->assertOk()
            ->assertJsonPath('data.id', $hasilId);

        $this->assertNotNull($response->json('data.nilai'));
    }
}
