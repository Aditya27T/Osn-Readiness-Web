<?php

namespace App\Exceptions;

class LatihanMasihDigunakanException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Latihan sudah punya pengerjaan siswa dan tidak bisa dihapus.', 'LATIHAN_MASIH_DIGUNAKAN', 409, $detail);
    }
}
