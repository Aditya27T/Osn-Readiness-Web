<?php

namespace App\Contracts\Services;

use App\DTOs\SimulasiDimulai;
use App\Models\HasilSimulasi;
use App\Models\Simulasi;
use App\Models\User;

/**
 * Alur simulasi siswa: daftar, mulai, simpan jawaban, submit, dan review.
 *
 * selesaikanPenilaian diwarisi dari PenilaianServiceInterface supaya
 * NilaiUlangJob bisa memanggilnya tanpa tahu detail tiap jenis.
 */
interface SimulasiServiceInterface extends PenilaianServiceInterface
{
    /**
     * Daftar simulasi satu tingkat beserta sisa kuota percobaannya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function daftar(User $user, int $tingkatId): array;

    /**
     * Mulai percobaan baru, atau lanjutkan yang sedang berjalan.
     */
    public function mulai(User $user, Simulasi $simulasi): SimulasiDimulai;

    /**
     * Percobaan milik siswa beserta soalnya; milik orang lain dianggap
     * tidak ada.
     */
    public function ringkasan(User $user, int $hasilId): SimulasiDimulai;

    public function simpanJawaban(User $user, int $hasilId, int $soalId, ?string $jawaban): void;

    public function submit(User $user, int $hasilId): HasilSimulasi;

    /**
     * Percobaan yang sudah dinilai, untuk ditinjau bersama kunci dan
     * pembahasan. Yang belum dinilai ditolak.
     */
    public function review(User $user, int $hasilId): HasilSimulasi;

    /**
     * Tutup semua percobaan kedaluwarsa atas nama sistem. Mengembalikan
     * jumlah yang berhasil ditutup.
     */
    public function tutupKedaluwarsa(): int;
}
