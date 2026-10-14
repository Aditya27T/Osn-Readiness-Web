<?php

namespace App\Randomizers;

use App\Contracts\Randomizers\RandomizerInterface;

/**
 * Pengambil acak deterministik dengan xorshift32.
 *
 * Memakai PRNG sendiri, bukan mt_srand(), supaya mengacak tidak mengubah
 * state acak global yang dipakai bagian lain. Seed yang sama selalu
 * menghasilkan urutan yang sama, sehingga unit test bisa assert hasil
 * pengacakan secara persis.
 */
class SeededRandomizer implements RandomizerInterface
{
    private int $state;

    public function __construct(private readonly int $seed = 20261003)
    {
        // xorshift selalu kembali ke nol bila diinisialisasi dengan nol.
        $this->state = $this->seed !== 0 ? $this->seed : 1;
    }

    public function seed(): int
    {
        return $this->seed;
    }

    public function acak(array $items): array
    {
        $acak = array_values($items);

        // Fisher-Yates. Harus benar-benar menukar dua posisi; menyalin nilai
        // tanpa menyimpan nilai lama akan menggandakan satu elemen dan
        // mengurangi jumlah item unik.
        for ($i = count($acak) - 1; $i > 0; $i--) {
            $j = $this->acakSatu($i + 1);

            $simpanan = $acak[$i];
            $acak[$i] = $acak[$j];
            $acak[$j] = $simpanan;
        }

        return $acak;
    }

    public function ambilAcak(array $items, int $jumlah): array
    {
        if ($jumlah <= 0) {
            return [];
        }

        return array_slice($this->acak($items), 0, $jumlah);
    }

    /**
     * Bilangan bulat seragam pada rentang [0, $batas).
     */
    private function acakSatu(int $batas): int
    {
        $x = $this->state;
        $x ^= ($x << 13) & 0xFFFF_FFFF;
        $x ^= $x >> 17;
        $x ^= ($x << 5) & 0xFFFF_FFFF;
        $this->state = $x & 0xFFFF_FFFF;

        return $this->state % $batas;
    }
}
