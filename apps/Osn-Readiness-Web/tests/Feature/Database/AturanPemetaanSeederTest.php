<?php

namespace Tests\Feature\Database;

use App\Models\AturanPemetaan;
use App\Models\TingkatSeleksi;
use Database\Seeders\AturanPemetaanSeeder;
use Database\Seeders\TingkatSeleksiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AturanPemetaanSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_enam_belas_parameter_per_tingkat(): void
    {
        $this->seed(TingkatSeleksiSeeder::class);
        $this->seed(AturanPemetaanSeeder::class);

        $this->assertSame(2, TingkatSeleksi::count());
        $this->assertSame(32, AturanPemetaan::count());
    }

    public function test_passing_grade_berbeda_antara_kabupaten_dan_provinsi(): void
    {
        $this->seed(TingkatSeleksiSeeder::class);
        $this->seed(AturanPemetaanSeeder::class);

        $kabupaten = TingkatSeleksi::where('urutan', 1)->firstOrFail();
        $provinsi = TingkatSeleksi::where('urutan', 2)->firstOrFail();

        $ambil = fn (TingkatSeleksi $tingkat, string $parameter): string => AturanPemetaan::where('tingkat_id', $tingkat->id)
            ->where('parameter', $parameter)
            ->value('ketentuan');

        $this->assertSame('70', $ambil($kabupaten, 'passing_grade'));
        $this->assertSame('80', $ambil($provinsi, 'passing_grade'));
        $this->assertSame('50', $ambil($kabupaten, 'pretest_persen_mudah'));
        $this->assertSame('50', $ambil($provinsi, 'pretest_persen_mudah'));
    }

    public function test_seeder_bisa_dijalankan_ulang_tanpa_menimpa_perubahan_admin(): void
    {
        $this->seed(TingkatSeleksiSeeder::class);
        $this->seed(AturanPemetaanSeeder::class);

        $kabupaten = TingkatSeleksi::where('urutan', 1)->firstOrFail();

        AturanPemetaan::where('tingkat_id', $kabupaten->id)
            ->where('parameter', 'passing_grade')
            ->update(['ketentuan' => '95']);

        $this->seed(AturanPemetaanSeeder::class);

        $this->assertSame(32, AturanPemetaan::count());
        $this->assertSame(
            '95',
            AturanPemetaan::where('tingkat_id', $kabupaten->id)
                ->where('parameter', 'passing_grade')
                ->value('ketentuan'),
        );
    }

    public function test_seeder_dilewati_bila_tingkat_belum_ada(): void
    {
        $this->seed(AturanPemetaanSeeder::class);

        $this->assertSame(0, AturanPemetaan::count());
    }
}
