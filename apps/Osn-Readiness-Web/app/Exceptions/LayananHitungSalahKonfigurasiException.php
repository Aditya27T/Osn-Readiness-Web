<?php

namespace App\Exceptions;

class LayananHitungSalahKonfigurasiException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Layanan hitung salah konfigurasi.', 'LAYANAN_HITUNG_SALAH_KONFIGURASI', 502, $detail);
    }
}
