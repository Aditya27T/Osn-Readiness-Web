<?php

namespace App\Services;

use App\Contracts\Services\DashboardAdminServiceInterface;
use App\Models\HasilSimulasi;
use App\Models\Pretest;
use App\Models\QuizPengerjaan;
use App\Models\TingkatSeleksi;
use App\Models\User;

class DashboardAdminService implements DashboardAdminServiceInterface
{
    public function ringkasan(): array
    {
        $siswaAktif = User::query()
            ->where('is_active', true)
            ->role('siswa')
            ->count();

        $pengerjaan = [
            'pretest' => Pretest::count(),
            'latihan' => QuizPengerjaan::count(),
            'simulasi' => HasilSimulasi::count(),
        ];

        $perTingkat = TingkatSeleksi::query()
            ->orderBy('urutan')
            ->get()
            ->map(fn (TingkatSeleksi $tingkat): array => [
                'tingkat_id' => (int) $tingkat->id,
                'nama_tingkat' => (string) $tingkat->nama_tingkat,
                'urutan' => (int) $tingkat->urutan,
                'jumlah_siswa' => User::query()
                    ->where('is_active', true)
                    ->where('tingkat_aktif_id', $tingkat->id)
                    ->count(),
            ])
            ->all();

        return [
            'siswa_aktif' => $siswaAktif,
            'pengerjaan_per_jenis' => $pengerjaan,
            'siswa_per_tingkat_aktif' => $perTingkat,
            'rata_rata_nilai_per_jenis' => [
                'pretest' => round((float) Pretest::whereNotNull('nilai')->avg('nilai'), 2),
                'latihan' => round((float) QuizPengerjaan::whereNotNull('nilai')->avg('nilai'), 2),
                'simulasi' => round((float) HasilSimulasi::whereNotNull('nilai')->avg('nilai'), 2),
            ],
        ];
    }
}
