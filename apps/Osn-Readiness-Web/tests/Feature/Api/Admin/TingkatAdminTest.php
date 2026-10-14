<?php

namespace Tests\Feature\Api\Admin;

use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TingkatAdminTest extends TestCase
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

    public function test_siswa_cannot_list_tingkat_admin(): void
    {
        $siswa = $this->createSiswa();

        $response = $this->actingAs($siswa)->getJson('/api/admin/tingkat');

        $response->assertForbidden();
    }

    public function test_admin_can_list_tingkat(): void
    {
        $admin = $this->createAdmin();
        TingkatSeleksi::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/tingkat');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_show_tingkat(): void
    {
        $admin = $this->createAdmin();
        $tingkat = TingkatSeleksi::factory()->create();

        $response = $this->actingAs($admin)->getJson("/api/admin/tingkat/{$tingkat->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $tingkat->id)
            ->assertJsonPath('data.nama_tingkat', $tingkat->nama_tingkat);
    }

    public function test_admin_can_update_tingkat(): void
    {
        $admin = $this->createAdmin();
        $tingkat = TingkatSeleksi::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/admin/tingkat/{$tingkat->id}", [
            'nama_tingkat' => 'Updated Tingkat',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nama_tingkat', 'Updated Tingkat');

        $this->assertDatabaseHas('tingkat_seleksi', [
            'id' => $tingkat->id,
            'nama_tingkat' => 'Updated Tingkat',
        ]);
    }

    public function test_urutan_tingkat_tidak_bisa_diubah(): void
    {
        $admin = $this->createAdmin();
        $tingkat = TingkatSeleksi::factory()->create(['urutan' => 7]);

        $this->actingAs($admin)
            ->putJson("/api/admin/tingkat/{$tingkat->id}", ['urutan' => 99, 'deskripsi' => 'Baru'])
            ->assertOk();

        $this->assertDatabaseHas('tingkat_seleksi', ['id' => $tingkat->id, 'urutan' => 7, 'deskripsi' => 'Baru']);
    }

    public function test_tingkat_tidak_bisa_ditambah_atau_dihapus(): void
    {
        $admin = $this->createAdmin();
        $tingkat = TingkatSeleksi::factory()->create();

        $this->actingAs($admin)->postJson('/api/admin/tingkat', ['nama_tingkat' => 'Nasional'])->assertStatus(405);
        $this->actingAs($admin)->deleteJson("/api/admin/tingkat/{$tingkat->id}")->assertStatus(405);
    }
}
