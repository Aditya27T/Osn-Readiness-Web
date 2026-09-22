<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_login_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('user');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['user', 'token'],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_failed_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_me_success(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'email', 'is_active', 'roles'],
            ]);
    }

    public function test_me_unauthenticated(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    public function test_logout_success(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Logout berhasil']);
    }

    public function test_inactive_user_blocked(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertForbidden()
            ->assertJson(['message' => 'Akun tidak aktif']);
    }
}
