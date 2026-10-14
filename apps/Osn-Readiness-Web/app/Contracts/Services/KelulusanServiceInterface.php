<?php

namespace App\Contracts\Services;

use App\Models\HasilSimulasi;

/**
 * Terapkan aturan kelulusan setelah satu simulasi selesai dinilai (BE-10).
 *
 * Dipanggil di dalam transaksi kedua SimulasiService::selesaikanPenilaian,
 * jadi juga berjalan ketika penilaian berasal dari NilaiUlangJob. Kontrak
 * ini dibuat di B0-B dan baru diisi di B3-A.
 */
interface KelulusanServiceInterface
{
    /**
     * @return array{lulus: bool, tingkat_tujuan_id: int|null}
     */
    public function menilai(HasilSimulasi $hasil): array;
}
