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
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakePerhitunganClient;
use Tests\TestCase;

class SyaratSimulasiApiTest extends TestCase
{
    use RefreshDatabase;

    private FakePerhitunganClient $perhitungan;

    private User $siswa;

    private TingkatSeleksi $tingkat;

    private Materi $materiSatu;

    private Materi $materiDua;

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
        $this->materiSatu = Materi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'kompetensi_id' => $kompetensi->id,
        ]);
        $this->materiDua = Materi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'kompetensi_id' => $kompetensi->id,
        ]);
    }

    private function buatPutaranAktif(): Pretest
    {
        $pretest = Pretest::factory()->create([
            'user_id' => $this->siswa->id,
            'tingkat_id' => $this->tingkat->id,
            'nilai' => 60.0,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);

        // Dua materi wajib, prioritas 1 dan 2.
        foreach ([$this->materiSatu, $this->materiDua] as $urutan => $materi) {
            RekomendasiMateri::create([
                'pretest_id' => $pretest->id,
                'user_id' => $this->siswa->id,
                'materi_id' => $materi->id,
                'prioritas' => $urutan + 1,
            ]);
        }

        return $pretest;
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/simulasi/syarat?tingkat_id='.$this->tingkat->id)->assertUnauthorized();
    }

    public function test_tanpa_putaran_aktif_belum_terpenuhi_tanpa_rincian(): void
    {
        $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat?tingkat_id='.$this->tingkat->id)
            ->assertOk()
            ->assertJsonPath('data.terpenuhi', false)
            ->assertJsonPath('data.alasan', 'belum_pretest')
            ->assertJsonPath('data.rincian', []);
    }

    public function test_dengan_putaran_aktif_alasan_kosong(): void
    {
        $this->buatPutaranAktif();

        $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat?tingkat_id='.$this->tingkat->id)
            ->assertOk()
            ->assertJsonPath('data.alasan', null);
    }

    public function test_tingkat_terkunci_dibalas_403(): void
    {
        $provinsi = TingkatSeleksi::factory()->create(['urutan' => 2]);

        $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat?tingkat_id='.$provinsi->id)
            ->assertForbidden()
            ->assertJsonPath('kode', 'TINGKAT_TERKUNCI');
    }

    public function test_tanpa_tingkat_id_dibalas_422(): void
    {
        $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tingkat_id');
    }

    public function test_rincian_materi_wajib_benar_sebelum_dilengkapi(): void
    {
        $this->buatPutaranAktif();

        $rincian = $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat?tingkat_id='.$this->tingkat->id)
            ->assertOk()
            ->assertJsonPath('data.terpenuhi', false)
            ->json('data.rincian');

        $this->assertCount(2, $rincian);
        $this->assertSame($this->materiSatu->id, $rincian[0]['materi_id']);
        $this->assertSame(1, $rincian[0]['prioritas']);
        $this->assertFalse($rincian[0]['selesai']);
        $this->assertNull($rincian[0]['nilai_latihan']);
        $this->assertSame(50.0, (float) $rincian[0]['batas']);
    }

    public function test_materi_wajib_tanpa_quiz_ditandai_belum_tersedia(): void
    {
        $this->buatPutaranAktif();

        // Materi satu punya quiz, materi dua tidak.
        Quiz::create([
            'materi_id' => $this->materiSatu->id,
            'nama_quiz' => 'Quiz satu',
            'jumlah_soal' => 10,
        ]);

        $rincian = $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat?tingkat_id='.$this->tingkat->id)
            ->assertOk()
            ->json('data.rincian');

        $this->assertFalse($rincian[0]['latihan_belum_tersedia']);
        $this->assertTrue($rincian[1]['latihan_belum_tersedia']);
        $this->assertFalse($rincian[1]['selesai']);
    }

    public function test_semua_lokas_dan_progress_selesai_akan_terpenuhi(): void
    {
        $this->buatPutaranAktif();

        foreach ([$this->materiSatu, $this->materiDua] as $materi) {
            $quiz = Quiz::create([
                'materi_id' => $materi->id,
                'nama_quiz' => 'Quiz '.$materi->judul,
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
                'materi_id' => $materi->id,
                'status' => 'selesai',
                'persentase' => 100,
                'tanggal_selesai' => now(),
            ]);
        }

        $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat?tingkat_id='.$this->tingkat->id)
            ->assertOk()
            ->assertJsonPath('data.terpenuhi', true);

        $rincian = $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat?tingkat_id='.$this->tingkat->id)
            ->json('data.rincian');

        foreach ($rincian as $baris) {
            $this->assertTrue($baris['selesai']);
            $this->assertSame(80.0, (float) $baris['nilai_latihan']);
        }
    }

    public function test_nilai_latihan_di_bawah_batas_tidak_membuka_simulasi(): void
    {
        $this->buatPutaranAktif();

        foreach ([$this->materiSatu, $this->materiDua] as $materi) {
            $quiz = Quiz::create([
                'materi_id' => $materi->id,
                'nama_quiz' => 'Quiz rendah '.$materi->judul,
                'jumlah_soal' => 10,
            ]);

            QuizPengerjaan::create([
                'user_id' => $this->siswa->id,
                'quiz_id' => $quiz->id,
                'nilai' => 30.0,
                'disubmit_pada' => now(),
                'selesai_pada' => now(),
            ]);

            ProgressBelajar::create([
                'user_id' => $this->siswa->id,
                'materi_id' => $materi->id,
                'status' => 'selesai',
                'persentase' => 100,
                'tanggal_selesai' => now(),
            ]);
        }

        $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat?tingkat_id='.$this->tingkat->id)
            ->assertOk()
            ->assertJsonPath('data.terpenuhi', false);
    }
}
