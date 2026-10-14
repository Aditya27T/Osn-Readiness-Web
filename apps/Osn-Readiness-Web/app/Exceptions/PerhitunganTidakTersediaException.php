<?php

namespace App\Exceptions;

/**
 * Layanan hitung gagal sementara, misalnya timeout, koneksi terputus, atau 5xx
 * setelah retry. Balasan API memakai kode HASIL_SEDANG_DIPROSES (503) dan
 * job akan mencoba lagi.
 */
class PerhitunganTidakTersediaException extends HasilSedangDiprosesException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($detail, $message ?? 'Layanan hitung sedang tidak tersedia.');
    }
}
