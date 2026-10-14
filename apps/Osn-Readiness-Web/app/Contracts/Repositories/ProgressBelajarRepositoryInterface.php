<?php

namespace App\Contracts\Repositories;

use App\Models\ProgressBelajar;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ProgressBelajarRepositoryInterface
{
    /**
     * Semua baris progress milik satu siswa untuk satu tingkat, diindeks
     * materi_id supaya pemanggil tinggal mengambil tanpa query ulang.
     *
     * @return Collection<int, ProgressBelajar>
     */
    public function untukUserDiTingkat(User $user, int $tingkatId): Collection;

    /**
     * Satu baris progress untuk satu pasang siswa dan materi, bila ada.
     */
    public function find(User $user, int $materiId): ?ProgressBelajar;

    /**
     * Buat atau perbarui baris progress milik satu siswa untuk satu materi.
     *
     * @param  array{status: string, persentase?: int, tanggal_selesai?: object|null}  $data
     */
    public function simpan(User $user, int $materiId, array $data): ProgressBelajar;

    /**
     * Hapus semua baris progress milik satu siswa untuk materi-materi yang
     * diberikan, dipakai saat putaran habis.
     *
     * @param  list<int>  $materiIds
     */
    public function hapusUntukMateri(User $user, array $materiIds): void;
}
