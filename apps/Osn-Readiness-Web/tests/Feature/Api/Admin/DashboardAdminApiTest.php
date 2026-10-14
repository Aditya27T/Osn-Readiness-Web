<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Pretest;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    private TingkatSeleksi $tingkat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->siswa = User::factory()->create();
        $this->siswa->assignRole('siswa');

        $this->tingkat = TingkatSeleksi::factory()->create(['urutan' => 1]);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    public function test_siswa_dilarang_melihat_dashboard(): void
    {
        $this->actingAs($this->siswa)
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    public function test_ringkasan_menghitung_angka_dengan_benar(): void
    {
        // Dua siswa aktif; satu mengarahkan tingkat aktifnya ke tingkat ini.
        $siswaDua = User::factory()->create(['tingkat_aktif_id' => $this->tingkat->id]);
        $siswaDua->assignRole('siswa');

        $this->siswa->update(['tingkat_aktif_id' => $this->tingkat->id]);

        // Satu siswa nonaktif tidak ikut dihitung.
        $nonaktif = User::factory()->create(['is_active' => false]);
        $nonaktif->assignRole('siswa');

        Pretest::create([
            'user_id' => $this->siswa->id,
            'tingkat_id' => $this->tingkat->id,
            'nilai' => 80.0,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);

        Pretest::create([
            'user_id' => $this->siswa->id,
            'tingkat_id' => $this->tingkat->id,
            'nilai' => 60.0,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->json('data');

        $this->assertSame(2, $data['siswa_aktif']);
        $this->assertSame(2, $data['pengerjaan_per_jenis']['pretest']);
        $this->assertSame(0, $data['pengerjaan_per_jenis']['latihan']);
        $this->assertSame(0, $data['pengerjaan_per_jenis']['simulasi']);
        $this->assertSame(70.0, (float) $data['rata_rata_nilai_per_jenis']['pretest']);

        $kabupaten = collect($data['siswa_per_tingkat_aktif'])
            ->firstWhere('tingkat_id', $this->tingkat->id);

        $this->assertSame(2, $kabupaten['jumlah_siswa']);
    }
}
