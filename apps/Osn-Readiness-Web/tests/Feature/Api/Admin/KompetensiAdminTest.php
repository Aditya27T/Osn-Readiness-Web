<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KompetensiAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        return $admin;
    }

    private function createSiswa(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('siswa');

        return $user;
    }

    public function test_siswa_cannot_list_kompetensi(): void
    {
        $siswa = $this->createSiswa();

        $response = $this->actingAs($siswa)->getJson('/api/admin/kompetensi');

        $response->assertForbidden();
    }

    public function test_admin_can_list_kompetensi(): void
    {
        $admin = $this->createAdmin();
        Kompetensi::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/kompetensi');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_kompetensi(): void
    {
        $admin = $this->createAdmin();
        $tingkat = TingkatSeleksi::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/kompetensi', [
            'tingkat_id' => $tingkat->id,
            'nama_kompetensi' => 'Kompetensi Baru',
            'deskripsi' => 'Deskripsi kompetensi',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nama_kompetensi', 'Kompetensi Baru');

        $this->assertDatabaseHas('kompetensi', [
            'tingkat_id' => $tingkat->id,
            'nama_kompetensi' => 'Kompetensi Baru',
        ]);
    }

    public function test_admin_can_update_kompetensi(): void
    {
        $admin = $this->createAdmin();
        $kompetensi = Kompetensi::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/admin/kompetensi/{$kompetensi->id}", [
            'nama_kompetensi' => 'Updated Kompetensi',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nama_kompetensi', 'Updated Kompetensi');
    }

    public function test_admin_can_delete_kompetensi(): void
    {
        $admin = $this->createAdmin();
        $kompetensi = Kompetensi::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/admin/kompetensi/{$kompetensi->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Kompetensi berhasil dihapus']);
    }

    public function test_admin_cannot_delete_kompetensi_with_materi(): void
    {
        $admin = $this->createAdmin();
        $kompetensi = Kompetensi::factory()->create();
        Materi::factory()->create(['kompetensi_id' => $kompetensi->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/kompetensi/{$kompetensi->id}");

        $response->assertStatus(409);
    }

    public function test_validation_error_on_create(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson('/api/admin/kompetensi', [
            'tingkat_id' => 999,
            'nama_kompetensi' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nama_kompetensi', 'tingkat_id']);
    }

    public function test_nama_kompetensi_unik_per_tingkat(): void
    {
        $admin = $this->createAdmin();
        $ada = Kompetensi::factory()->create(['nama_kompetensi' => 'Logika']);
        $lain = Kompetensi::factory()->create(['tingkat_id' => $ada->tingkat_id, 'nama_kompetensi' => 'Aljabar']);

        $this->actingAs($admin)
            ->postJson('/api/admin/kompetensi', [
                'tingkat_id' => $ada->tingkat_id,
                'nama_kompetensi' => 'Logika',
                'deskripsi' => 'Kembar',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nama_kompetensi');

        $this->actingAs($admin)
            ->putJson("/api/admin/kompetensi/{$lain->id}", ['nama_kompetensi' => 'Logika'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nama_kompetensi');

        // Nama yang sama di tingkat lain boleh.
        $this->actingAs($admin)
            ->postJson('/api/admin/kompetensi', [
                'tingkat_id' => TingkatSeleksi::factory()->create()->id,
                'nama_kompetensi' => 'Logika',
                'deskripsi' => 'Tingkat lain',
            ])
            ->assertCreated();
    }
}
