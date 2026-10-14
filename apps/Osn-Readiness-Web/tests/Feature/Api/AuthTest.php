<?php

namespace Tests\Feature\Api;

use App\Contracts\Services\AuthServiceInterface;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_register_creates_siswa_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Siswa Baru',
            'email' => 'siswa@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => ['user' => ['id', 'name', 'email', 'is_active', 'tingkat_aktif_id', 'roles'], 'token'],
            ])
            ->assertJsonPath('data.user.is_active', true)
            ->assertJsonPath('data.user.roles.0', 'siswa');

        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('users', [
            'email' => 'siswa@example.com',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $response->json('data.user.id'),
            'role_id' => $this->peranSiswa(),
        ]);
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'ada@example.com',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_invalid_email_format(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Siswa',
            'email' => 'bukan-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_email_yang_sudah_dihapus_bisa_dipakai_lagi(): void
    {
        User::factory()->create(['email' => 'lama@example.com'])->delete();

        $this->postJson('/api/auth/register', [
            'name' => 'Siswa Baru',
            'email' => 'lama@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();
    }

    public function test_login_success_returns_token(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'test@example.com');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame(1, PersonalAccessToken::count());
    }

    public function test_login_email_tidak_dikenal_dan_password_salah_balas_401_yang_sama(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);

        $salahPassword = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password-salah',
        ]);

        $emailTidakDikenal = $this->postJson('/api/auth/login', [
            'email' => 'tidak@example.com',
            'password' => 'password',
        ]);

        foreach ([$salahPassword, $emailTidakDikenal] as $response) {
            $response->assertUnauthorized()
                ->assertJsonPath('kode', 'KREDENSIAL_TIDAK_VALID')
                ->assertJsonPath('message', 'Email atau password salah.');
        }

        $this->assertSame(
            $salahPassword->json('message'),
            $emailTidakDikenal->json('message'),
        );
    }

    public function test_login_akun_nonaktif_balas_403(): void
    {
        User::factory()->tidakAktif()->create([
            'email' => 'nonaktif@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'nonaktif@example.com',
            'password' => 'password',
        ])->assertForbidden()
            ->assertJsonPath('kode', 'AKUN_TIDAK_AKTIF');
    }

    public function test_login_dibatasi_lima_kali_per_menit(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);

        for ($percobaan = 1; $percobaan <= 5; $percobaan++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ])->assertOk();
        }

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])->assertStatus(429);
    }

    public function test_login_tidak_membocorkan_keberadaan_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'tidak@example.com',
            'password' => 'password12345',
        ]);

        $response->assertUnauthorized()->assertJsonStructure(['message', 'kode', 'detail']);
    }

    public function test_me_returns_tingkat_aktif_id(): void
    {
        $tingkat = TingkatSeleksi::factory()->provinsi()->create();
        $user = User::factory()->create(['tingkat_aktif_id' => $tingkat->id]);

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.tingkat_aktif_id', $tingkat->id)
            ->assertJsonStructure(['message', 'data' => ['id', 'name', 'email', 'is_active', 'tingkat_aktif_id', 'roles']]);
    }

    public function test_me_tingkat_aktif_id_kosong_saat_siswa_belum_mulai_pretest(): void
    {
        $user = User::factory()->create(['tingkat_aktif_id' => null]);

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.tingkat_aktif_id', null);
    }

    public function test_me_dan_logout_tanpa_token_balas_401(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_logout_mencabut_hanya_token_yang_dipakai(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);

        $tokenSatu = $this->login('test@example.com')->json('data.token');
        $tokenDua = $this->login('test@example.com')->json('data.token');

        $this->assertSame(2, PersonalAccessToken::count());

        $this->withToken($tokenSatu)->postJson('/api/auth/logout')->assertOk();

        // Auth guard menyimpan user yang sudah ter-resolve, jadi harus dibuang
        // supaya request berikutnya benar-benar memverifikasi token lagi.
        $this->app['auth']->forgetGuards();

        $this->withToken($tokenSatu)->getJson('/api/auth/me')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($tokenDua)->getJson('/api/auth/me')->assertOk();

        $this->assertSame(1, PersonalAccessToken::count());
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_akun_yang_dinonaktifkan_langsung_ditolak(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);
        $token = $this->login('test@example.com')->json('data.token');

        $user->forceFill(['is_active' => false])->save();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akun tidak aktif');
    }

    public function test_update_profile_mengubah_nama_dan_email(): void
    {
        $user = User::factory()->create(['name' => 'Lama', 'email' => 'lama@example.com']);

        $response = $this->actingAs($user)->putJson('/api/auth/profile', [
            'name' => 'Baru',
            'email' => 'Baru@Example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Profil berhasil diperbarui')
            ->assertJsonPath('data.name', 'Baru')
            ->assertJsonPath('data.email', 'baru@example.com')
            ->assertJsonStructure(['message', 'data' => ['id', 'name', 'email', 'roles']]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Baru', 'email' => 'baru@example.com']);
    }

    public function test_update_profile_tanpa_token_balas_401(): void
    {
        $this->putJson('/api/auth/profile', [
            'name' => 'Baru',
            'email' => 'baru@example.com',
        ])->assertUnauthorized();
    }

    public function test_update_profile_validasi_gagal_balas_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/auth/profile', [
            'name' => '',
            'email' => 'bukan-email',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_update_profile_menolak_email_milik_akun_lain(): void
    {
        User::factory()->create(['email' => 'lain@example.com']);
        $user = User::factory()->create(['email' => 'saya@example.com']);

        $this->actingAs($user)->putJson('/api/auth/profile', [
            'name' => 'Saya',
            'email' => 'LAIN@example.com',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_update_profile_boleh_memakai_email_sendiri(): void
    {
        $user = User::factory()->create(['name' => 'Lama', 'email' => 'saya@example.com']);

        $this->actingAs($user)->putJson('/api/auth/profile', [
            'name' => 'Baru',
            'email' => 'saya@example.com',
        ])->assertOk()->assertJsonPath('data.name', 'Baru');
    }

    public function test_update_profile_mendukung_patch(): void
    {
        $user = User::factory()->create(['email' => 'saya@example.com']);

        $this->actingAs($user)->patchJson('/api/auth/profile', [
            'name' => 'Via Patch',
            'email' => 'saya@example.com',
        ])->assertOk()->assertJsonPath('data.name', 'Via Patch');
    }

    private function login(string $email): TestResponse
    {
        return $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'password',
        ])->assertOk();
    }

    private function peranSiswa(): int
    {
        return Role::where('name', 'siswa')->value('id');
    }

    public function test_email_disimpan_dan_dicocokkan_tanpa_membedakan_huruf_besar(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Siswa',
            'email' => 'Siswa.Baru@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonPath('data.user.email', 'siswa.baru@example.com');

        // Email yang sama dengan huruf berbeda bukan akun baru.
        $this->postJson('/api/auth/register', [
            'name' => 'Kembar',
            'email' => 'SISWA.BARU@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/auth/login', [
            'email' => 'SISWA.baru@EXAMPLE.com',
            'password' => 'password123',
        ])->assertOk();
    }

    public function test_akun_lama_dengan_email_huruf_besar_tetap_bisa_login(): void
    {
        User::factory()->create(['email' => 'Lama@Example.com', 'password' => 'password']);

        $this->postJson('/api/auth/login', [
            'email' => 'lama@example.com',
            'password' => 'password',
        ])->assertOk();
    }

    public function test_pendaftaran_bersamaan_dengan_email_sama_dibalas_gagal_validasi(): void
    {
        User::factory()->create(['email' => 'kembar@example.com']);

        // Validasi request sudah dilewati request lain yang lebih dulu;
        // yang tersisa hanya index unik di database.
        try {
            $this->app->make(AuthServiceInterface::class)->register([
                'name' => 'Kembar',
                'email' => 'kembar@example.com',
                'password' => 'password123',
            ]);

            $this->fail('Email kembar harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_token_kedaluwarsa_ditolak(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/auth/me')->assertOk();

        // Masa berlaku token 7 hari (keputusan #3).
        $this->travel(7)->days();
        $this->travel(1)->minutes();
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_gagal_login_tidak_dicatat_sebagai_error(): void
    {
        Log::spy();

        $this->postJson('/api/auth/login', [
            'email' => 'tidak.ada@example.com',
            'password' => 'salah',
        ])->assertUnauthorized();

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    public function test_pembersihan_token_kedaluwarsa_terjadwal_harian(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('sanctum:prune-expired', Artisan::output());
    }
}
