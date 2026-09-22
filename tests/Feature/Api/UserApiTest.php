<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_returns_403(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson('/api/users');

        $response->assertForbidden();
    }

    public function test_super_admin_can_list_users(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        User::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($admin)->getJson('/api/users');

        $response->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_super_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'email', 'is_active', 'roles'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_super_admin_can_show_user(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');

        $response = $this->actingAs($admin)->getJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'email'],
            ]);
    }

    public function test_super_admin_can_update_user(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');

        $response = $this->actingAs($admin)->putJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    public function test_super_admin_can_delete_user(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');

        $response = $this->actingAs($admin)->deleteJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJson(['message' => 'User berhasil dihapus']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_validation_error_returns_422(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_password_not_in_response(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->getJson('/api/users');

        $response->assertOk();

        foreach ($response->json('data') as $user) {
            $this->assertArrayNotHasKey('password', $user);
            $this->assertArrayNotHasKey('remember_token', $user);
        }
    }

    public function test_pagination_works(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        User::factory()->count(20)->create(['is_active' => true]);

        $response = $this->actingAs($admin)->getJson('/api/users?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data');

        $this->assertEquals(5, $response->json('meta.per_page'));
    }
}
