<?php

namespace App\Exceptions;

/**
 * Balasan layanan hitung tidak bisa dipercaya: 403 atau 422, atau isinya tidak
 * cocok dengan yang dikirim. Balasan API memakai kode
 * LAYANAN_HITUNG_SALAH_KONFIGURASI (502) dan dicatat sebagai error kritis
 * karena tidak akan membaik dengan mencoba lagi.
 */
class PerhitunganKonfigurasiException extends LayananHitungSalahKonfigurasiException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($detail, $message ?? 'Layanan hitung mengembalikan jawaban yang tidak sesuai.');
    }
}
