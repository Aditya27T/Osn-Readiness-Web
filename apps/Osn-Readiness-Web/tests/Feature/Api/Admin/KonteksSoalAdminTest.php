<?php

namespace Tests\Feature\Api\Admin;

use App\Models\KonteksSoal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KonteksSoalAdminTest extends TestCase
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

    public function test_admin_can_list_konteks_soal(): void
    {
        $admin = $this->createAdmin();
        KonteksSoal::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/konteks-soal');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_konteks_soal(): void
    {
        $admin = $this->createAdmin();
        $tingkat = TingkatSeleksi::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/konteks-soal', [
            'tingkat_id' => $tingkat->id,
            'judul' => 'Konteks Soal Baru',
            'isi_konteks' => 'Isi konteks soal',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.judul', 'Konteks Soal Baru');

        $this->assertDatabaseHas('konteks_soal', [
            'tingkat_id' => $tingkat->id,
            'judul' => 'Konteks Soal Baru',
        ]);
    }

    public function test_admin_can_update_konteks_soal(): void
    {
        $admin = $this->createAdmin();
        $konteks = KonteksSoal::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/admin/konteks-soal/{$konteks->id}", [
            'judul' => 'Updated Konteks',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.judul', 'Updated Konteks');
    }

    public function test_admin_can_delete_konteks_soal(): void
    {
        $admin = $this->createAdmin();
        $konteks = KonteksSoal::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/admin/konteks-soal/{$konteks->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Konteks Soal berhasil dihapus']);
    }
}
