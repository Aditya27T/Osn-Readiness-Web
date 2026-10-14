<?php

namespace App\Services\Admin;

use App\Contracts\Repositories\SoalRepositoryInterface;
use App\Contracts\Services\SoalAdminServiceInterface;
use App\Enums\TipeSoal;
use App\Guards\SoalGuard;
use App\Models\Pembahasan;
use App\Models\Soal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SoalAdminService implements SoalAdminServiceInterface
{
    public function __construct(
        private readonly SoalRepositoryInterface $soalRepository,
    ) {}

    public function daftar(int $perPage = 15): LengthAwarePaginator
    {
        return $this->soalRepository->paginasiAdmin($perPage);
    }

    public function buat(array $data, ?UploadedFile $gambar = null): Soal
    {
        $data = $this->rapikan($data);

        if ($gambar instanceof UploadedFile) {
            $data['gambar'] = $gambar->store('soal', 'public');
        } else {
            unset($data['gambar']);
        }

        /** @var Soal */
        return $this->soalRepository->create($data);
    }

    public function perbarui(Soal $soal, array $data, ?UploadedFile $gambar = null): Soal
    {
        SoalGuard::validateUpdate($soal, $data);

        $data = $this->rapikan($data);
        unset($data['gambar']);

        if ($gambar instanceof UploadedFile) {
            // Gambar lama dihapus hanya bila soal belum dipakai; pengerjaan
            // yang sedang berjalan tidak ikut berubah di tengah jalan.
            if ($soal->gambar !== null && ! SoalGuard::isInUse($soal)) {
                Storage::disk('public')->delete($soal->gambar);
            }

            $data['gambar'] = $gambar->store('soal', 'public');
        }

        /** @var Soal */
        return $this->soalRepository->update($soal, $data);
    }

    public function hapus(Soal $soal): void
    {
        $this->soalRepository->delete($soal);
    }

    public function simpanPembahasan(Soal $soal, string $isi): Pembahasan
    {
        return $this->soalRepository->simpanPembahasan($soal, $isi);
    }

    public function hapusPembahasan(Soal $soal): bool
    {
        return $this->soalRepository->hapusPembahasan($soal);
    }

    /**
     * Soal isian tidak menyimpan pilihan, termasuk saat tipe soal diganti
     * dari pilihan ganda ke isian.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rapikan(array $data): array
    {
        if (($data['tipe_soal'] ?? null) === TipeSoal::Isian->value) {
            $data['pilihan_jawaban'] = null;
        }

        return $data;
    }
}
