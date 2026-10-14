<?php

namespace Tests\Feature\Api;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\Quiz;
use App\Models\QuizPengerjaan;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakePerhitunganClient;
use Tests\TestCase;

class RiwayatApiTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private TingkatSeleksi $tingkat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $fake = new FakePerhitunganClient;
        $this->app->instance(PerhitunganClientInterface::class, $fake);

        $this->siswa = User::factory()->create();
        $this->siswa->assignRole('siswa');

        $this->tingkat = TingkatSeleksi::factory()->create(['urutan' => 1]);

        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $this->tingkat->id]);
        Materi::factory()->create(['tingkat_id' => $this->tingkat->id, 'kompetensi_id' => $kompetensi->id]);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/riwayat')->assertUnauthorized();
    }

    public function test_riwayat_terbaru_dulu_dengan_pagination(): void
    {
        $this->buatPretestSelesai(60.0);
        $this->buatLatihanSelesai(80.0);

        $data = $this->actingAs($this->siswa)
            ->getJson('/api/riwayat')
            ->assertOk()
            ->json('data');

        // Latihan selesai belakangan, jadi muncul pertama.
        $this->assertSame('latihan', $data[0]['jenis_hasil']);
        $this->assertSame(80.0, (float) $data[0]['nilai']);
        $this->assertSame('pretest', $data[1]['jenis_hasil']);
        $this->assertSame(60.0, (float) $data[1]['nilai']);

        $this->actingAs($this->siswa)
            ->getJson('/api/riwayat?per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_jenis_menyaring_baris(): void
    {
        $this->buatPretestSelesai(60.0);
        $this->buatLatihanSelesai(80.0);

        $data = $this->actingAs($this->siswa)
            ->getJson('/api/riwayat?jenis=pretest')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('pretest', $data[0]['jenis_hasil']);
    }

    public function test_filter_tingkat_dan_jenis_liar_dibalas_422(): void
    {
        $this->actingAs($this->siswa)
            ->getJson('/api/riwayat?jenis=bukan-jenis')
            ->assertUnprocessable();

        $this->actingAs($this->siswa)
            ->getJson('/api/riwayat?tingkat_id='.$this->tingkat->id)
            ->assertOk();
    }

    public function test_riwayat_orang_lain_tidak_terlihat(): void
    {
        $this->buatPretestSelesai(60.0);

        $penyusup = User::factory()->create();
        $penyusup->assignRole('siswa');

        $this->actingAs($penyusup)
            ->getJson('/api/riwayat')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_latihan_putaran_habis_hilang_dari_riwayat(): void
    {
        // Baris quiz_pengerjaan yang dihapus KelulusanService otomatis hilang
        // dari view, karena view hanya membaca baris yang masih ada.
        $this->buatLatihanSelesai(80.0);

        $this->actingAs($this->siswa)
            ->getJson('/api/riwayat?jenis=latihan')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        QuizPengerjaan::query()->delete();

        $this->actingAs($this->siswa)
            ->getJson('/api/riwayat?jenis=latihan')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    private function buatPretestSelesai(float $nilai): Pretest
    {
        return Pretest::factory()->create([
            'user_id' => $this->siswa->id,
            'tingkat_id' => $this->tingkat->id,
            'nilai' => $nilai,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);
    }

    private function buatLatihanSelesai(float $nilai): QuizPengerjaan
    {
        $materi = Materi::query()->where('tingkat_id', $this->tingkat->id)->firstOrFail();

        $quiz = Quiz::create([
            'materi_id' => $materi->id,
            'nama_quiz' => 'Quiz tes',
            'jumlah_soal' => 10,
        ]);

        return QuizPengerjaan::create([
            'user_id' => $this->siswa->id,
            'quiz_id' => $quiz->id,
            'nilai' => $nilai,
            'disubmit_pada' => now(),
            'selesai_pada' => now()->addMinute(),
        ]);
    }
}
