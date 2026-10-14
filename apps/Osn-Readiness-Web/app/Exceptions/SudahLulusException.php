<?php

namespace App\Exceptions;

class SudahLulusException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Tingkat ini sudah lulus.', 'SUDAH_LULUS', 409, $detail);
    }
}
