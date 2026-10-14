<?php

namespace App\Exceptions;

class KuotaSimulasiHabisException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Kuota percobaan simulasi sudah habis.', 'KUOTA_SIMULASI_HABIS', 409, $detail);
    }
}
