<?php

namespace App\Exceptions;

class BankSoalTidakCukupException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Bank soal tidak cukup untuk menyusun paket.', 'BANK_SOAL_TIDAK_CUKUP', 503, $detail);
    }
}
