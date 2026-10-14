<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\QuizPengerjaan;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatihanAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    private Materi $materi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->siswa = User::factory()->create();
        $this->siswa->assignRole('siswa');

        $tingkat = TingkatSeleksi::factory()->create();
        $this->materi = Materi::factory()->create([
            'tingkat_id' => $tingkat->id,
            'kompetensi_id' => Kompetensi::factory()->create(['tingkat_id' => $tingkat->id])->id,
        ]);
    }

    private function latihan(): Quiz
    {
        return Quiz::factory()->create(['materi_id' => $this->materi->id, 'nama_quiz' => 'Latihan awal']);
    }

    public function test_tanpa_token_dibalas_401(): void
    {
        $this->getJson('/api/admin/latihan')->assertUnauthorized();
    }

    public function test_siswa_dibalas_403(): void
    {
        $latihan = $this->latihan();

        $this->actingAs($this->siswa)->getJson('/api/admin/latihan')->assertForbidden();
        $this->actingAs($this->siswa)->deleteJson("/api/admin/latihan/{$latihan->id}")->assertForbidden();
    }

    public function test_daftar_dan_detail(): void
    {
        $latihan = $this->latihan();

        $this->actingAs($this->admin)->getJson('/api/admin/latihan')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->admin)->getJson("/api/admin/latihan/{$latihan->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $latihan->id)
            ->assertJsonPath('data.nama_quiz', 'Latihan awal')
            ->assertJsonPath('data.materi.id', $this->materi->id);
    }

    public function test_detail_latihan_yang_tidak_ada_dibalas_404(): void
    {
        $this->actingAs($this->admin)->getJson('/api/admin/latihan/999999')->assertNotFound();
    }

    public function test_tambah_latihan(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/latihan', [
                'materi_id' => $this->materi->id,
                'nama_quiz' => 'Latihan baru',
                'jumlah_soal' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('data.nama_quiz', 'Latihan baru');

        $this->assertDatabaseHas('quiz', ['materi_id' => $this->materi->id, 'jumlah_soal' => 10]);
    }

    public function test_satu_materi_hanya_boleh_satu_latihan(): void
    {
        $this->latihan();

        $this->actingAs($this->admin)
            ->postJson('/api/admin/latihan', [
                'materi_id' => $this->materi->id,
                'nama_quiz' => 'Latihan kedua',
                'jumlah_soal' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('materi_id');
    }

    public function test_jumlah_soal_di_bawah_latihan_min_soal_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/latihan', [
                'materi_id' => $this->materi->id,
                'nama_quiz' => 'Terlalu sedikit',
                'jumlah_soal' => 9,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah_soal');

        $latihan = $this->latihan();

        $this->actingAs($this->admin)
            ->putJson("/api/admin/latihan/{$latihan->id}", [
                'nama_quiz' => 'Latihan awal',
                'jumlah_soal' => 9,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah_soal');
    }

    public function test_ubah_latihan_benar_benar_tersimpan(): void
    {
        $latihan = $this->latihan();

        $this->actingAs($this->admin)
            ->putJson("/api/admin/latihan/{$latihan->id}", [
                'nama_quiz' => 'Nama baru',
                'jumlah_soal' => 12,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $latihan->id)
            ->assertJsonPath('data.nama_quiz', 'Nama baru');

        $this->assertDatabaseHas('quiz', ['id' => $latihan->id, 'nama_quiz' => 'Nama baru', 'jumlah_soal' => 12]);
    }

    public function test_hapus_latihan_benar_benar_terhapus(): void
    {
        $latihan = $this->latihan();

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/latihan/{$latihan->id}")
            ->assertOk();

        $this->assertDatabaseMissing('quiz', ['id' => $latihan->id]);
    }

    public function test_hapus_latihan_yang_punya_pengerjaan_dibalas_409(): void
    {
        $latihan = $this->latihan();
        QuizPengerjaan::create(['user_id' => $this->siswa->id, 'quiz_id' => $latihan->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/latihan/{$latihan->id}")
            ->assertStatus(409)
            ->assertJsonPath('kode', 'LATIHAN_MASIH_DIGUNAKAN');

        $this->assertDatabaseHas('quiz', ['id' => $latihan->id]);
    }
}
