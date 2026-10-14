<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaAdminTest extends TestCase
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

    private function buatSiswa(int $jumlah = 1, bool $aktif = true): User
    {
        $siswa = User::factory()->count($jumlah)->create(['is_active' => $aktif]);
        $siswa->each->assignRole('siswa');

        return $siswa->last();
    }

    public function test_admin_can_list_siswa(): void
    {
        $admin = $this->createAdmin();
        $this->buatSiswa(5);

        $response = $this->actingAs($admin)->getJson('/api/admin/siswa');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_paginate_siswa(): void
    {
        $admin = $this->createAdmin();
        $this->buatSiswa(20);

        $response = $this->actingAs($admin)->getJson('/api/admin/siswa?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_admin_can_show_siswa(): void
    {
        $admin = $this->createAdmin();
        $siswa = $this->buatSiswa();

        $response = $this->actingAs($admin)->getJson("/api/admin/siswa/{$siswa->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $siswa->id)
            ->assertJsonPath('data.email', $siswa->email);
    }

    public function test_admin_can_update_siswa(): void
    {
        $admin = $this->createAdmin();
        $siswa = $this->buatSiswa();

        $response = $this->actingAs($admin)->putJson("/api/admin/siswa/{$siswa->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $siswa->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_deactivate_siswa(): void
    {
        $admin = $this->createAdmin();
        $siswa = $this->buatSiswa();

        $response = $this->actingAs($admin)->postJson("/api/admin/siswa/{$siswa->id}/deactivate");

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', [
            'id' => $siswa->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_deactivate_revokes_tokens(): void
    {
        $admin = $this->createAdmin();
        $siswa = $this->buatSiswa();

        // Create a token for the siswa
        $token = $siswa->createToken('test-token');

        $this->actingAs($admin)->postJson("/api/admin/siswa/{$siswa->id}/deactivate");

        // Token should be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $siswa->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_siswa_nonaktif_tetap_tampil_dan_bisa_dikelola(): void
    {
        $admin = $this->createAdmin();
        $this->buatSiswa(5);
        $nonaktif = $this->buatSiswa(3, aktif: false);

        $this->actingAs($admin)->getJson('/api/admin/siswa')
            ->assertOk()
            ->assertJsonCount(8, 'data');

        $this->actingAs($admin)->getJson("/api/admin/siswa/{$nonaktif->id}")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin)->deleteJson("/api/admin/siswa/{$nonaktif->id}")->assertOk();

        $this->assertSoftDeleted('users', ['id' => $nonaktif->id]);
    }

    public function test_daftar_siswa_tidak_memuat_admin(): void
    {
        $admin = $this->createAdmin();
        $this->createAdmin();
        $this->buatSiswa(2);

        $roles = $this->actingAs($admin)->getJson('/api/admin/siswa')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data.*.roles');

        foreach ($roles as $role) {
            $this->assertSame(['siswa'], $role);
        }
    }

    public function test_akun_admin_tidak_bisa_dikelola_lewat_menu_siswa(): void
    {
        $admin = $this->createAdmin();
        $adminLain = $this->createAdmin();

        foreach ([$admin, $adminLain] as $sasaran) {
            $this->actingAs($admin)->getJson("/api/admin/siswa/{$sasaran->id}")->assertNotFound();
            $this->actingAs($admin)->putJson("/api/admin/siswa/{$sasaran->id}", ['name' => 'X'])->assertNotFound();
            $this->actingAs($admin)->postJson("/api/admin/siswa/{$sasaran->id}/deactivate")->assertNotFound();
            $this->actingAs($admin)->deleteJson("/api/admin/siswa/{$sasaran->id}")->assertNotFound();
        }

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true, 'deleted_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $adminLain->id, 'is_active' => true, 'deleted_at' => null]);
    }

    public function test_soft_delete_mencabut_semua_token_siswa(): void
    {
        $admin = $this->createAdmin();
        $siswa = $this->buatSiswa();
        $token = $siswa->createToken('api')->plainTextToken;

        $this->actingAs($admin)->deleteJson("/api/admin/siswa/{$siswa->id}")->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $siswa->id,
            'tokenable_type' => User::class,
        ]);

        // Token lama benar-benar tidak bisa dipakai lagi.
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_validation_error_on_update(): void
    {
        $admin = $this->createAdmin();
        $siswa = $this->buatSiswa();

        $response = $this->actingAs($admin)->putJson("/api/admin/siswa/{$siswa->id}", [
            'email' => 'invalid-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/admin/siswa')->assertUnauthorized();
    }

    public function test_siswa_dilarang_mengakses_daftar_siswa(): void
    {
        $siswa = $this->buatSiswa();
        $siswa->assignRole('siswa');

        $this->actingAs($siswa)
            ->getJson('/api/admin/siswa')
            ->assertForbidden();
    }

    public function test_admin_dapat_soft_delete_siswa(): void
    {
        $admin = $this->createAdmin();
        $siswa = $this->buatSiswa();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/siswa/{$siswa->id}")
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $siswa->id]);
    }

    public function test_password_tidak_ikut_dalam_respons(): void
    {
        $admin = $this->createAdmin();
        $this->buatSiswa();

        $this->actingAs($admin)
            ->getJson('/api/admin/siswa')
            ->assertOk()
            ->assertJsonMissing(['password' => 'password']);
    }
}
