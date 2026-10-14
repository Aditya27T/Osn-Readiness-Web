<?php

namespace App\Exceptions;

class SimulasiBelumDinilaiException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Simulasi sudah disubmit tetapi belum dinilai.', 'SIMULASI_BELUM_DINILAI', 409, $detail);
    }
}
