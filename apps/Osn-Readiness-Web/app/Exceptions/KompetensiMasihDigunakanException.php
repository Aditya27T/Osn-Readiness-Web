<?php

namespace App\Exceptions;

class KompetensiMasihDigunakanException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Kompetensi masih memiliki materi dan tidak bisa dihapus.', 'KOMPETENSI_MASIH_DIGUNAKAN', 409, $detail);
    }
}
