<?php

namespace App\Exceptions;

class PutaranMasihBerjalanException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Putaran masih berjalan.', 'PUTARAN_MASIH_BERJALAN', 409, $detail);
    }
}
