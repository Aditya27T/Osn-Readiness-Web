<?php

namespace App\Contracts\Randomizers;

/**
 * Sumber acak untuk pengambilan soal.
 *
 * Disuntikkan, bukan memakai helper acak Laravel, supaya unit test bisa
 * memastikan hasil yang sama untuk seed yang sama.
 */
interface RandomizerInterface
{
    /**
     * Acak seluruh isi. Mengembalikan array dengan kunci baru berurutan.
     *
     * @template T
     *
     * @param  array<int|string, T>  $items
     * @return array<int, T>
     */
    public function acak(array $items): array;

    /**
     * Ambil sebagian isi secara acak. Bila jumlah melebihi yang tersedia,
     * semua isi dikembalikan.
     *
     * @template T
     *
     * @param  array<int|string, T>  $items
     * @return array<int, T>
     */
    public function ambilAcak(array $items, int $jumlah): array;
}
