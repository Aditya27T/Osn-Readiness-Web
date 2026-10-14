<?php

namespace Tests\Unit\Randomizers;

use App\Randomizers\AcakRandomizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AcakRandomizerTest extends TestCase
{
    #[Test]
    public function acak_mempertahankan_semua_nilai_dengan_kunci_berurutan(): void
    {
        $items = ['a' => 'soal-1', 9 => 'soal-2', 'z' => 'soal-3'];

        $hasil = (new AcakRandomizer)->acak($items);

        $this->assertSame([0, 1, 2], array_keys($hasil));
        $this->assertEqualsCanonicalizing(array_values($items), $hasil);
        $this->assertSame([], (new AcakRandomizer)->acak([]));
    }

    #[Test]
    #[DataProvider('jumlahPengambilan')]
    public function ambil_acak_memenuhi_jumlah_tanpa_duplikasi(int $jumlah, int $diharapkan): void
    {
        $items = ['a' => 'soal-1', 9 => 'soal-2', 'z' => 'soal-3'];

        $hasil = (new AcakRandomizer)->ambilAcak($items, $jumlah);

        $this->assertCount($diharapkan, $hasil);
        $this->assertCount($diharapkan, array_unique($hasil));
        $this->assertSame(array_values($hasil), $hasil);
        $this->assertSame([], array_diff($hasil, $items));
    }

    public static function jumlahPengambilan(): array
    {
        return [[-1, 0], [0, 0], [1, 1], [2, 2], [3, 3], [5, 3]];
    }

    #[Test]
    public function bank_kosong_menghasilkan_array_kosong(): void
    {
        $this->assertSame([], (new AcakRandomizer)->ambilAcak([], 5));
    }
}
