<?php

namespace App\Exceptions;

class WaktuHabisException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Waktu simulasi sudah habis.', 'WAKTU_HABIS', 409, $detail);
    }
}
