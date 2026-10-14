<?php

namespace App\Exceptions;

class HasilSedangDiprosesException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Hasil sedang diproses, coba lagi nanti.', 'HASIL_SEDANG_DIPROSES', 503, $detail);
    }
}
