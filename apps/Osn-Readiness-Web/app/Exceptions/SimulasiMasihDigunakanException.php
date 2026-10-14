<?php

namespace App\Exceptions;

class SimulasiMasihDigunakanException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Simulasi sudah punya hasil pengerjaan siswa dan tidak bisa dihapus.', 'SIMULASI_MASIH_DIGUNAKAN', 409, $detail);
    }
}
