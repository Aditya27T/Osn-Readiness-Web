<?php

namespace App\Contracts\Repositories;

use App\Models\Materi;
use Illuminate\Database\Eloquent\Collection;

interface MateriRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Materi satu tingkat dalam urutan tampilnya.
     *
     * @return Collection<int, Materi>
     */
    public function untukTingkat(int $tingkatId): Collection;

    /**
     * Daftar materi untuk admin, opsional disaring per kompetensi.
     *
     * @return Collection<int, Materi>
     */
    public function daftarAdmin(?int $kompetensiId = null): Collection;

    /**
     * Alasan materi belum boleh dihapus, atau null bila tidak dirujuk apa
     * pun. Yang dihitung: soal (termasuk yang di-soft delete, karena barisnya
     * masih merujuk materi), latihan, dan hasil siswa (pemetaan, materi
     * wajib, progress belajar).
     */
    public function alasanTidakBisaDihapus(Materi $materi): ?string;
}
