<?php

namespace App\Enums;

/**
 * Tahap siswa di satu tingkat, dihitung dari nilai turunan PutaranService.
 * Dipakai frontend untuk memutuskan aksi yang boleh dikerjakan.
 */
enum TahapSiswa: string
{
    case BelumPretest = 'BELUM_PRETEST';
    case PretestBerjalan = 'PRETEST_BERJALAN';
    case Belajar = 'BELAJAR';
    case SiapSimulasi = 'SIAP_SIMULASI';
    case SimulasiBerjalan = 'SIMULASI_BERJALAN';
    case PutaranHabis = 'PUTARAN_HABIS';
    case Lulus = 'LULUS';
}
