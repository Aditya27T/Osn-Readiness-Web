<?php

namespace App\Exceptions;

class SyaratSimulasiBelumTerpenuhiException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Syarat simulasi belum terpenuhi.', 'SYARAT_SIMULASI_BELUM_TERPENUHI', 409, $detail);
    }
}
