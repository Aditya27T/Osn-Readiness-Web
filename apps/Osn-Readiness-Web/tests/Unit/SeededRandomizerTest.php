<?php

namespace Tests\Unit;

use App\Randomizers\SeededRandomizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeededRandomizerTest extends TestCase
{
    #[Test]
    public function mengacak_tidak_pernah_menghapus_atau_menggandakan_item(): void
    {
        $acak = (new SeededRandomizer(4242))->acak(range(1, 200));

        $this->assertCount(200, $acak);
        $this->assertCount(200, array_unique($acak));

        $terurut = $acak;
        sort($terurut);
        $this->assertSame(range(1, 200), $terurut);
    }

    #[Test]
    public function seed_sama_menghasilkan_urutan_sama(): void
    {
        $satu = (new SeededRandomizer(777))->acak(range(1, 100));
        $dua = (new SeededRandomizer(777))->acak(range(1, 100));

        $this->assertSame($satu, $dua);
    }

    #[Test]
    public function seed_berbeda_menghasilkan_urutan_berbeda(): void
    {
        $satu = (new SeededRandomizer(777))->acak(range(1, 100));
        $dua = (new SeededRandomizer(778))->acak(range(1, 100));

        $this->assertNotSame($satu, $dua);
    }

    #[Test]
    public function seed_nol_tidak_membekukan_acakan(): void
    {
        $acak = (new SeededRandomizer(0))->acak(range(1, 100));

        $this->assertCount(100, $acak);
        $this->assertCount(100, array_unique($acak));
        $this->assertNotSame(range(1, 100), $acak);
    }

    #[Test]
    public function seed_besar_tidak_membekukan_acakan(): void
    {
        $acak = (new SeededRandomizer(PHP_INT_MAX))->acak(range(1, 100));

        $this->assertCount(100, array_unique($acak));
    }

    #[Test]
    public function urutan_asli_dan_kunci_asli_tetap_utuh(): void
    {
        $acak = (new SeededRandomizer(1))->acak(['a' => 'nilai-a', 'b' => 'nilai-b', 'c' => 'nilai-c']);

        $this->assertCount(3, $acak);

        foreach ($acak as $nilai) {
            $this->assertContains($nilai, ['nilai-a', 'nilai-b', 'nilai-c']);
        }
    }

    #[Test]
    public function ambil_acak_mengembalikan_sejumlah_yang_diminta(): void
    {
        $ambil = (new SeededRandomizer(99))->ambilAcak(range(1, 50), 10);

        $this->assertCount(10, $ambil);
        $this->assertCount(10, array_unique($ambil));
    }

    #[Test]
    public function ambil_acak_tidak_lebih_dari_yang_tersedia(): void
    {
        $ambil = (new SeededRandomizer(99))->ambilAcak(range(1, 5), 20);

        // Semua isi dikembalikan, urutannya tetap hasil acakan.
        $this->assertCount(5, $ambil);

        $terurut = $ambil;
        sort($terurut);
        $this->assertSame([1, 2, 3, 4, 5], $terurut);
    }

    #[Test]
    public function ambil_acak_nol_mengembalikan_kosong(): void
    {
        $this->assertSame([], (new SeededRandomizer(99))->ambilAcak(range(1, 5), 0));
    }

    #[Test]
    public function isi_kosong_ditangani(): void
    {
        $randomizer = new SeededRandomizer(5);

        $this->assertSame([], $randomizer->acak([]));
        $this->assertSame([], $randomizer->ambilAcak([], 5));
    }

    #[Test]
    public function satu_tidak_pernah_diacak(): void
    {
        $randomizer = new SeededRandomizer(5);

        $this->assertSame(['x'], $randomizer->acak(['x']));
        $this->assertSame(['x'], $randomizer->acak(['x']));
    }
}
