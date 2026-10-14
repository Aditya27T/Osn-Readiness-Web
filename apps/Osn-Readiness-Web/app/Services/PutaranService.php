<?php

namespace App\Services;

use App\Contracts\Repositories\HasilSimulasiRepositoryInterface;
use App\Contracts\Repositories\KenaikanTingkatRepositoryInterface;
use App\Contracts\Repositories\PretestRepositoryInterface;
use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Services\AturanServiceInterface;
use App\Contracts\Services\PutaranServiceInterface;
use App\Contracts\Services\SyaratSimulasiServiceInterface;
use App\DTOs\StatusPutaran;
use App\Enums\TahapSiswa;
use App\Models\Pretest;
use App\Models\TingkatSeleksi;
use App\Models\User;

class PutaranService implements PutaranServiceInterface
{
    public function __construct(
        private readonly TingkatSeleksiRepositoryInterface $tingkatRepository,
        private readonly PretestRepositoryInterface $pretestRepository,
        private readonly HasilSimulasiRepositoryInterface $hasilSimulasiRepository,
        private readonly KenaikanTingkatRepositoryInterface $kenaikanRepository,
        private readonly AturanServiceInterface $aturanService,
        private readonly SyaratSimulasiServiceInterface $syaratSimulasiService,
    ) {}

    public function status(User $user, TingkatSeleksi $tingkat): StatusPutaran
    {
        $tingkatTerbuka = $this->tingkatTerbuka($user, $tingkat);
        $sudahLulus = $this->kenaikanRepository->adaLulus($user->id, $tingkat->id);

        $pretestBerjalan = $this->pretestRepository->berjalan($user->id, $tingkat->id);
        $putaranAktif = $this->pretestRepository->putaranAktifTerbaru($user->id, $tingkat->id);

        $percobaanTerpakai = $putaranAktif instanceof Pretest
            ? $this->hasilSimulasiRepository->jumlahPercobaan($putaranAktif->id)
            : 0;

        $simulasiBerjalan = $putaranAktif instanceof Pretest
            ? $this->hasilSimulasiRepository->berjalan($user->id, $putaranAktif->id)
            : null;

        $aturan = $this->aturanService->untukTingkat($tingkat->id);

        $putaranHabis = $percobaanTerpakai >= $aturan->simulasiMaksPercobaan
            && $simulasiBerjalan === null
            && ! $sudahLulus;

        $bolehPretestBaru = $tingkatTerbuka
            && ! $sudahLulus
            && $pretestBerjalan === null
            && ($putaranAktif === null || $putaranHabis);

        $tahap = $this->tentukanTahap(
            user: $user,
            tingkat: $tingkat,
            sudahLulus: $sudahLulus,
            pretestBerjalan: $pretestBerjalan !== null,
            putaranAktif: $putaranAktif !== null,
            simulasiBerjalan: $simulasiBerjalan !== null,
            putaranHabis: $putaranHabis,
            percobaanTerpakai: $percobaanTerpakai,
            maksPercobaan: $aturan->simulasiMaksPercobaan,
        );

        return new StatusPutaran(
            tingkatId: $tingkat->id,
            tingkatTerbuka: $tingkatTerbuka,
            sudahLulus: $sudahLulus,
            pretestBerjalanId: $pretestBerjalan?->id,
            putaranAktifId: $putaranAktif?->id,
            percobaanTerpakai: $percobaanTerpakai,
            simulasiBerjalanId: $simulasiBerjalan?->id,
            putaranHabis: $putaranHabis,
            bolehPretestBaru: $bolehPretestBaru,
            tahap: $tahap,
            sisaKuotaSimulasi: max(0, (int) $aturan->simulasiMaksPercobaan - $percobaanTerpakai),
        );
    }

    private function tingkatTerbuka(User $user, TingkatSeleksi $tingkat): bool
    {
        if ($tingkat->urutan === 1) {
            return true;
        }

        $sebelumnya = $this->tingkatRepository->semuaTerurut()
            ->firstWhere('urutan', $tingkat->urutan - 1);

        return $sebelumnya instanceof TingkatSeleksi
            && $this->kenaikanRepository->adaLulus($user->id, $sebelumnya->id);
    }

    private function tentukanTahap(
        User $user,
        TingkatSeleksi $tingkat,
        bool $sudahLulus,
        bool $pretestBerjalan,
        bool $putaranAktif,
        bool $simulasiBerjalan,
        bool $putaranHabis,
        int $percobaanTerpakai,
        int $maksPercobaan,
    ): TahapSiswa {
        if ($sudahLulus) {
            return TahapSiswa::Lulus;
        }

        if ($pretestBerjalan) {
            return TahapSiswa::PretestBerjalan;
        }

        if ($simulasiBerjalan) {
            return TahapSiswa::SimulasiBerjalan;
        }

        if (! $putaranAktif) {
            return TahapSiswa::BelumPretest;
        }

        if ($putaranHabis) {
            return TahapSiswa::PutaranHabis;
        }

        $syarat = $this->syaratSimulasiService->periksa($user, $tingkat);

        if ($syarat->terpenuhi && $percobaanTerpakai < $maksPercobaan) {
            return TahapSiswa::SiapSimulasi;
        }

        return TahapSiswa::Belajar;
    }
}
