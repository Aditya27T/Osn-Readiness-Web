<?php

namespace App\Enums;

/**
 * Jenis pengerjaan yang muncul di view riwayat_hasil dan di NilaiUlangJob.
 * Nilainya sama dengan Peruntukan, tapi maknanya berbeda: Peruntukan describing
 * soal, JenisPengerjaan describing baris hasil.
 */
enum JenisPengerjaan: string
{
    case Pretest = 'pretest';
    case Latihan = 'latihan';
    case Simulasi = 'simulasi';
}
