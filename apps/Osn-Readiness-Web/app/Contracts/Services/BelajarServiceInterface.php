<?php

namespace App\Contracts\Services;

use App\Models\Materi;
use App\Models\ProgressBelajar;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Membaca materi dan mencatat progress belajar siswa.
 *
 * Materi bisa dibaca kapan saja selama tingkatnya terbuka; menandai selesai
 * hanya dicatat saat ada putaran aktif (BE-06).
 */
interface BelajarServiceInterface
{
    /**
     * Daftar materi satu tingkat dengan tanda wajib, prioritas, status
     * progress, dan nilai latihan terbaik. Materi wajib ditampilkan lebih
     * dulu, diurutkan prioritas lalu urutan materi.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function daftar(User $user, int $tingkatId): Collection;

    /**
     * Satu baris materi dengan tanda wajib, progress, dan nilai latihan.
     *
     * @return array<string, mixed>
     */
    public function detail(User $user, Materi $materi): array;

    /**
     * Buat atau perbarui satu baris progress belajar.
     */
    public function perbaruiProgress(User $user, int $materiId, string $status): ProgressBelajar;
}
