<?php

namespace App\Contracts\Services;

interface PenilaianServiceInterface
{
    /**
     * Selesaikan penilaian satu pengerjaan dan simpan hasilnya.
     *
     * Idempoten: bila selesai_pada sudah terisi, method berhenti tanpa menulis
     * apa pun. Itu yang membuat NilaiUlangJob aman dijalankan berulang kali.
     */
    public function selesaikanPenilaian(int $id): void;
}
