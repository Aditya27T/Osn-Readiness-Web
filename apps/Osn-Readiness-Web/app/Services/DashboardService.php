<?php

namespace App\Services;

use App\Contracts\Repositories\HasilSimulasiRepositoryInterface;
use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Services\DashboardServiceInterface;
use App\Contracts\Services\PutaranServiceInterface;
use App\Contracts\Services\SyaratSimulasiServiceInterface;
use App\Models\TingkatSeleksi;
use App\Models\User;

class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        private readonly PutaranServiceInterface $putaranService,
        private readonly SyaratSimulasiServiceInterface $syaratService,
        private readonly TingkatSeleksiRepositoryInterface $tingkatRepository,
        private readonly HasilSimulasiRepositoryInterface $hasilRepository,
    ) {}

    public function untuk(User $user): array
    {
        $tingkatSemua = $this->tingkatRepository->semuaTerurut();
        $daftar = [];

        foreach ($tingkatSemua as $tingkat) {
            $daftar[] = $this->perTingkat($user, $tingkat);
        }

        $aktif = $user->tingkat_aktif_id === null
            ? null
            : $tingkatSemua->firstWhere('id', (int) $user->tingkat_aktif_id);

        return [
            'tingkat_aktif_id' => $user->tingkat_aktif_id === null ? null : (int) $user->tingkat_aktif_id,
            'tingkat_aktif' => $aktif?->nama_tingkat,
            'tingkat' => $daftar,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function perTingkat(User $user, TingkatSeleksi $tingkat): array
    {
        $status = $this->putaranService->status($user, $tingkat);
        $syarat = $this->syaratService->periksa($user, $tingkat);

        $terakhir = $status->putaranAktifId === null
            ? null
            : $this->hasilRepository->terakhirSelesai($user->id, (int) $status->putaranAktifId);

        return [
            'tingkat_id' => (int) $tingkat->id,
            'nama_tingkat' => (string) $tingkat->nama_tingkat,
            'urutan' => (int) $tingkat->urutan,
            'tingkat_terbuka' => $status->tingkatTerbuka,
            'tahap' => $status->tahap->value,
            'sudah_lulus' => $status->sudahLulus,
            'sisa_kuota_simulasi' => $status->sisaKuotaSimulasi,
            'syarat_simulasi' => [
                'terpenuhi' => $syarat->terpenuhi,
                'alasan' => $syarat->alasan,
                'rincian' => $syarat->rincian,
            ],
            'hasil_simulasi_terakhir' => $terakhir === null ? null : [
                'id' => (int) $terakhir->id,
                'nilai' => $terakhir->nilai === null ? null : (float) $terakhir->nilai,
                'lulus' => $terakhir->lulus,
                'selesai_pada' => $terakhir->selesai_pada?->toISOString(),
            ],
        ];
    }
}
