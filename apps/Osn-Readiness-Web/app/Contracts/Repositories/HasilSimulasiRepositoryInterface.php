<?php

namespace App\Contracts\Repositories;

use App\Models\HasilSimulasi;
use Illuminate\Database\Eloquent\Collection;

interface HasilSimulasiRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Simulasi yang belum selesai dinilai pada putaran ini, kalau ada.
     */
    public function berjalan(int $userId, int $pretestId): ?HasilSimulasi;

    /**
     * Jumlah percobaan simulasi yang sudah dipakai pada satu putaran.
     */
    public function jumlahPercobaan(int $pretestId): int;

    /**
     * Jumlah percobaan yang sudah selesai dinilai pada satu putaran, dipakai
     * KelulusanService untuk tahu apakah ini kegagalan terakhir.
     */
    public function jumlahSelesai(int $pretestId): int;

    /**
     * Satu percobaan milik siswa ini; milik orang lain dianggap tidak ada.
     */
    public function findMilik(int $id, int $userId): ?HasilSimulasi;

    /**
     * Satu percobaan dengan kunci baris, untuk transaksi submit.
     */
    public function findUntukUpdate(int $id): ?HasilSimulasi;

    /**
     * Percobaan yang belum disubmit tetapi batasnya ditambah toleransi sudah
     * lewat, untuk ditutup scheduler.
     *
     * @return Collection<int, HasilSimulasi>
     */
    public function kedaluwarsa(int $batasToleransiDetik): Collection;

    /**
     * Nilai tertinggi dari percobaan yang sudah selesai dinilai pada satu
     * putaran, dipakai sebagai nilai terbaik di kenaikan tidak_lulus.
     */
    public function nilaiTerbaik(int $pretestId): ?float;

    /**
     * Percobaan terakhir yang sudah selesai dinilai pada satu putaran milik
     * satu siswa, untuk layar dashboard.
     */
    public function terakhirSelesai(int $userId, int $pretestId): ?HasilSimulasi;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): HasilSimulasi;
}
