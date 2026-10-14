<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\PemetaanMateri;
use App\Models\ProgressBelajar;
use App\Models\Quiz;
use App\Models\RekomendasiMateri;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MateriAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        return $admin;
    }

    public function test_admin_can_list_materi(): void
    {
        $admin = $this->createAdmin();
        Materi::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/materi');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_materi_by_kompetensi(): void
    {
        $admin = $this->createAdmin();
        $kompetensi1 = Kompetensi::factory()->create();
        $kompetensi2 = Kompetensi::factory()->create();

        Materi::factory()->count(2)->create(['kompetensi_id' => $kompetensi1->id]);
        Materi::factory()->create(['kompetensi_id' => $kompetensi2->id]);

        $response = $this->actingAs($admin)->getJson("/api/admin/materi?kompetensi_id={$kompetensi1->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_materi(): void
    {
        $admin = $this->createAdmin();
        $tingkat = TingkatSeleksi::factory()->create();
        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $tingkat->id]);

        $response = $this->actingAs($admin)->postJson('/api/admin/materi', [
            'tingkat_id' => $tingkat->id,
            'kompetensi_id' => $kompetensi->id,
            'urutan' => 1,
            'judul' => 'Materi Baru',
            'deskripsi' => 'Deskripsi materi',
            'isi_materi' => 'Isi materi lengkap',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.judul', 'Materi Baru');
    }

    public function test_admin_can_update_materi(): void
    {
        $admin = $this->createAdmin();
        $materi = Materi::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/admin/materi/{$materi->id}", [
            'judul' => 'Updated Materi',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.judul', 'Updated Materi');
    }

    public function test_unggah_gambar_membalas_alamat_tanpa_domain(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson('/api/admin/materi/gambar', [
            'gambar' => UploadedFile::fake()->image('materi.png', 640, 480),
        ]);

        $response->assertCreated();

        $path = $response->json('data.path');

        $this->assertStringStartsWith('/storage/materi/', $path);
        Storage::disk('public')->assertExists(substr($path, strlen('/storage/')));
    }

    public function test_unggah_selain_gambar_atau_lebih_dari_2mb_ditolak(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->postJson('/api/admin/materi/gambar', ['gambar' => UploadedFile::fake()->create('document.pdf', 100)])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->postJson('/api/admin/materi/gambar', ['gambar' => UploadedFile::fake()->image('besar.png')->size(2049)])
            ->assertUnprocessable();
    }

    public function test_tanpa_token_401_dan_siswa_403(): void
    {
        $this->getJson('/api/admin/materi')->assertUnauthorized();

        $siswa = User::factory()->create();
        $siswa->assignRole('siswa');

        $this->actingAs($siswa)->getJson('/api/admin/materi')->assertForbidden();
        $this->actingAs($siswa)
            ->postJson('/api/admin/materi/gambar', ['gambar' => UploadedFile::fake()->image('materi.png')])
            ->assertForbidden();
    }

    public function test_hapus_materi_yang_tidak_dirujuk(): void
    {
        $materi = Materi::factory()->create();

        $this->actingAs($this->createAdmin())
            ->deleteJson("/api/admin/materi/{$materi->id}")
            ->assertOk();

        $this->assertDatabaseMissing('materi', ['id' => $materi->id]);
    }

    public function test_hapus_materi_yang_masih_dirujuk_dibalas_409(): void
    {
        $admin = $this->createAdmin();
        $siswa = User::factory()->create();

        $perujuk = [
            'soal' => fn (Materi $m) => Soal::factory()->create(['materi_id' => $m->id]),
            'soal terhapus' => fn (Materi $m) => Soal::factory()->create(['materi_id' => $m->id])->delete(),
            'latihan' => fn (Materi $m) => Quiz::factory()->create(['materi_id' => $m->id]),
            'pemetaan' => fn (Materi $m) => PemetaanMateri::factory()->create(['materi_id' => $m->id]),
            'materi wajib' => fn (Materi $m) => RekomendasiMateri::factory()->create(['materi_id' => $m->id]),
            'progress' => fn (Materi $m) => ProgressBelajar::create([
                'user_id' => $siswa->id,
                'materi_id' => $m->id,
                'status' => 'belajar',
                'persentase' => 10,
            ]),
        ];

        foreach ($perujuk as $nama => $buat) {
            $materi = Materi::factory()->create();
            $buat($materi);

            $this->actingAs($admin)
                ->deleteJson("/api/admin/materi/{$materi->id}")
                ->assertStatus(409, "Materi yang dirujuk {$nama} harus ditolak.")
                ->assertJsonPath('kode', 'MATERI_MASIH_DIGUNAKAN');

            $this->assertDatabaseHas('materi', ['id' => $materi->id]);
        }
    }

    public function test_kompetensi_harus_setingkat_dengan_materi(): void
    {
        $tingkat = TingkatSeleksi::factory()->create();
        $kompetensiLain = Kompetensi::factory()->create();

        $this->actingAs($this->createAdmin())
            ->postJson('/api/admin/materi', [
                'tingkat_id' => $tingkat->id,
                'kompetensi_id' => $kompetensiLain->id,
                'urutan' => 1,
                'judul' => 'Materi',
                'deskripsi' => 'Deskripsi',
                'isi_materi' => 'Isi',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kompetensi_id');
    }

    public function test_pindah_kompetensi_ke_tingkat_lain_ditolak(): void
    {
        $tingkat = TingkatSeleksi::factory()->create();
        $materi = Materi::factory()->create([
            'tingkat_id' => $tingkat->id,
            'kompetensi_id' => Kompetensi::factory()->create(['tingkat_id' => $tingkat->id])->id,
        ]);

        $this->actingAs($this->createAdmin())
            ->putJson("/api/admin/materi/{$materi->id}", ['kompetensi_id' => Kompetensi::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kompetensi_id');
    }

    public function test_urutan_unik_per_tingkat(): void
    {
        $admin = $this->createAdmin();
        $tingkat = TingkatSeleksi::factory()->create();
        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $tingkat->id]);

        $pertama = Materi::factory()->create(['tingkat_id' => $tingkat->id, 'kompetensi_id' => $kompetensi->id, 'urutan' => 1]);
        $kedua = Materi::factory()->create(['tingkat_id' => $tingkat->id, 'kompetensi_id' => $kompetensi->id, 'urutan' => 2]);

        $this->actingAs($admin)
            ->postJson('/api/admin/materi', [
                'tingkat_id' => $tingkat->id,
                'kompetensi_id' => $kompetensi->id,
                'urutan' => 1,
                'judul' => 'Kembar',
                'deskripsi' => 'Deskripsi',
                'isi_materi' => 'Isi',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('urutan');

        $this->actingAs($admin)
            ->putJson("/api/admin/materi/{$kedua->id}", ['urutan' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('urutan');

        // Mengirim ulang urutan miliknya sendiri bukan bentrok.
        $this->actingAs($admin)
            ->putJson("/api/admin/materi/{$pertama->id}", ['urutan' => 1, 'judul' => 'Judul baru'])
            ->assertOk();
    }

    public function test_judul_melebihi_panjang_kolom_ditolak_422(): void
    {
        $materi = Materi::factory()->create();

        $this->actingAs($this->createAdmin())
            ->putJson("/api/admin/materi/{$materi->id}", ['judul' => str_repeat('a', 201)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('judul');
    }
}
