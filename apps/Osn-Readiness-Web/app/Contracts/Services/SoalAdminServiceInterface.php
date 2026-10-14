<?php

namespace App\Contracts\Services;

use App\Models\Pembahasan;
use App\Models\Soal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

interface SoalAdminServiceInterface
{
    public function daftar(int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data, ?UploadedFile $gambar = null): Soal;

    /**
     * Ubah soal. Soal yang sudah dipakai pengerjaan siswa menolak perubahan
     * kunci, level, peruntukan, dan materi (422).
     *
     * @param  array<string, mixed>  $data
     */
    public function perbarui(Soal $soal, array $data, ?UploadedFile $gambar = null): Soal;

    /**
     * Soft delete. Berkas gambar dibiarkan karena pengerjaan dan review
     * yang sudah memuat soal ini masih menampilkannya.
     */
    public function hapus(Soal $soal): void;

    public function simpanPembahasan(Soal $soal, string $isi): Pembahasan;

    /**
     * False bila soal belum punya pembahasan.
     */
    public function hapusPembahasan(Soal $soal): bool;
}
