<?php

namespace App\Contracts\Services;

/**
 * Menghitung kecukupan bank soal untuk admin, memakai fungsi kuota yang sama
 * dengan SoalPickerService supaya laporan dan perilaku nyata tidak berbeda.
 */
interface BankSoalServiceInterface
{
    /**
     * Laporan lima bagian untuk satu tingkat: pre-test per level, pre-test
     * per materi, putaran pre-test, simulasi per level, dan latihan per
     * materi.
     *
     * @return array<string, mixed>
     */
    public function laporan(int $tingkatId): array;

    /**
     * Cukup bila tiap level punya soal simulasi minimal sekuota satu
     * percobaan. Dipakai guard is_aktif simulasi.
     */
    public function cukupUntukSimulasi(int $tingkatId, int $jumlahSoal): bool;
}
