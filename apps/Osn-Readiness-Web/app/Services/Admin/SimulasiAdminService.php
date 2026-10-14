<?php

namespace App\Services\Admin;

use App\Contracts\Repositories\SimulasiRepositoryInterface;
use App\Contracts\Services\BankSoalServiceInterface;
use App\Contracts\Services\SimulasiAdminServiceInterface;
use App\Exceptions\SimulasiMasihDigunakanException;
use App\Models\Simulasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class SimulasiAdminService implements SimulasiAdminServiceInterface
{
    public function __construct(
        private readonly SimulasiRepositoryInterface $simulasiRepository,
        private readonly BankSoalServiceInterface $bankSoalService,
    ) {}

    public function daftar(int $perPage = 15): LengthAwarePaginator
    {
        return $this->simulasiRepository->paginasiAdmin($perPage);
    }

    public function buat(array $data): Simulasi
    {
        if ((bool) ($data['is_aktif'] ?? false)) {
            $this->pastikanBankCukup((int) $data['tingkat_id'], (int) $data['jumlah_soal']);
        }

        /** @var Simulasi */
        return $this->simulasiRepository->create($data);
    }

    public function perbarui(Simulasi $simulasi, array $data): Simulasi
    {
        $aktif = (bool) ($data['is_aktif'] ?? $simulasi->is_aktif);
        $jumlahSoal = (int) ($data['jumlah_soal'] ?? $simulasi->jumlah_soal);

        // Bank diperiksa saat simulasi dinyalakan, atau saat jumlah soal
        // simulasi yang sedang aktif berubah. Mematikan selalu boleh, dan
        // mengubah nama simulasi aktif tidak ikut terhalang.
        $dinyalakan = $aktif && ! $simulasi->is_aktif;
        $jumlahBerubah = $aktif && $jumlahSoal !== (int) $simulasi->jumlah_soal;

        if ($dinyalakan || $jumlahBerubah) {
            $this->pastikanBankCukup((int) $simulasi->tingkat_id, $jumlahSoal);
        }

        /** @var Simulasi */
        return $this->simulasiRepository->update($simulasi, $data);
    }

    public function hapus(Simulasi $simulasi): void
    {
        if ($this->simulasiRepository->punyaHasil($simulasi)) {
            throw new SimulasiMasihDigunakanException;
        }

        $this->simulasiRepository->delete($simulasi);
    }

    /**
     * Guard is_aktif (BE-19).
     *
     * @throws ValidationException
     */
    private function pastikanBankCukup(int $tingkatId, int $jumlahSoal): void
    {
        if (! $this->bankSoalService->cukupUntukSimulasi($tingkatId, $jumlahSoal)) {
            throw ValidationException::withMessages([
                'is_aktif' => ['Bank soal tidak cukup untuk satu percobaan simulasi utuh.'],
            ]);
        }
    }
}
