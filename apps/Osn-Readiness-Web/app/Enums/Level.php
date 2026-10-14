<?php

namespace App\Enums;

enum Level: string
{
    case Mudah = 'mudah';
    case Sedang = 'sedang';
    case Sulit = 'sulit';

    /**
     * Posisi level dalam urutan mudah, sedang, sulit.
     *
     * Dipakai untuk meng indekskan kuota dan kandidat soal, sehingga array
     * selalu terurut dari level termudah ke tersulit.
     */
    public function index(): int
    {
        return array_search($this, self::cases(), strict: true);
    }
}
