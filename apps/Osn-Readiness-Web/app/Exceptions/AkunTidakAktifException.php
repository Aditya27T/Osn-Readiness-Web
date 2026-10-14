<?php

namespace App\Exceptions;

class AkunTidakAktifException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Akun tidak aktif.', 'AKUN_TIDAK_AKTIF', 403, $detail);
    }
}
