<?php

namespace App\Exceptions;

class KonteksSoalMasihDigunakanException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Konteks soal masih dipakai oleh soal dan tidak bisa dihapus.', 'KONTEKS_SOAL_MASIH_DIGUNAKAN', 409, $detail);
    }
}
