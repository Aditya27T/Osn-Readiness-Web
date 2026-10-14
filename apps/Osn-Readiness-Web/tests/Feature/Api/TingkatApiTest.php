<?php

namespace Tests\Feature\Api;

use App\Enums\StatusKenaikan;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\AturanPemetaanSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TingkatSeleksiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TingkatApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            TingkatSeleksiSeeder::class,
            AturanPemetaanSeeder::class,
        ]);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/tingkat')->assertUnauthorized();
    }

    public function test_akun_nonaktif_returns_403(): void
    {
        $user = User::factory()->tidakAktif()->create();

        $this->actingAs($user)->getJson('/api/tingkat')->assertForbidden();
    }

    public function test_mengembalikan_dua_tingkat_berurutan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/tingkat');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'message',
                'data' => [
                    ['id', 'nama_tingkat', 'deskripsi', 'urutan', 'tingkat_terbuka', 'tahap'],
                ],
            ]);

        $this->assertSame(1, $response->json('data.0.urutan'));
        $this->assertSame(2, $response->json('data.1.urutan'));
    }

    public function test_tingkat_baru_terbuka_di_kabupaten_dan_terkunci_di_provinsi(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/tingkat');

        $response->assertJsonPath('data.0.tingkat_terbuka', true)
            ->assertJsonPath('data.0.tahap', 'BELUM_PRETEST')
            ->assertJsonPath('data.1.tingkat_terbuka', false)
            ->assertJsonPath('data.1.tahap', 'BELUM_PRETEST');
    }

    public function test_tingkat_terbuka_dan_tahap_lulus_setelah_kenaikan(): void
    {
        $user = User::factory()->create();
        $kabupaten = TingkatSeleksi::where('urutan', 1)->firstOrFail();
        $provinsi = TingkatSeleksi::where('urutan', 2)->firstOrFail();

        $user->kenaikanTingkat()->create([
            'tingkat_asal_id' => $kabupaten->id,
            'tingkat_tujuan_id' => $provinsi->id,
            'status' => StatusKenaikan::Lulus,
        ]);

        $response = $this->actingAs($user)->getJson('/api/tingkat');

        $response->assertJsonPath('data.0.tahap', 'LULUS')
            ->assertJsonPath('data.1.tingkat_terbuka', true)
            ->assertJsonPath('data.1.tahap', 'BELUM_PRETEST');
    }

    public function test_pretest_berjalan_menampilkan_tahap_pretest_berjalan(): void
    {
        $user = User::factory()->create();
        $kabupaten = TingkatSeleksi::where('urutan', 1)->firstOrFail();

        $user->pretest()->create([
            'tingkat_id' => $kabupaten->id,
        ]);

        $this->actingAs($user)->getJson('/api/tingkat')
            ->assertJsonPath('data.0.tahap', 'PRETEST_BERJALAN');
    }

    public function test_putaran_aktif_dengan_syarat_belum_terpenuhi_menampilkan_belajar(): void
    {
        $user = User::factory()->create();
        $kabupaten = TingkatSeleksi::where('urutan', 1)->firstOrFail();

        $user->pretest()->create([
            'tingkat_id' => $kabupaten->id,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
            'nilai' => 70,
        ]);

        // Syarat simulasi belum diimplementasikan (B2-B), jadi belum terpenuhi.
        $this->actingAs($user)->getJson('/api/tingkat')
            ->assertJsonPath('data.0.tahap', 'BELAJAR');
    }
}
