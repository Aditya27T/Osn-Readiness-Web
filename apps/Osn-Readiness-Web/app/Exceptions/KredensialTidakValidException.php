<?php

namespace App\Exceptions;

class KredensialTidakValidException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Email atau password salah.', 'KREDENSIAL_TIDAK_VALID', 401, $detail);
    }
}
