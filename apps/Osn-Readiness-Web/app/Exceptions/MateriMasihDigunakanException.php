<?php

namespace App\Exceptions;

class MateriMasihDigunakanException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Materi masih memiliki soal, latihan, atau dirujuk hasil siswa dan tidak bisa dihapus.', 'MATERI_MASIH_DIGUNAKAN', 409, $detail);
    }
}
