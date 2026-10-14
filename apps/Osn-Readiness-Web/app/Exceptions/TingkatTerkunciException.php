<?php

namespace App\Exceptions;

class TingkatTerkunciException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Tingkat terkunci untuk siswa ini.', 'TINGKAT_TERKUNCI', 403, $detail);
    }
}
