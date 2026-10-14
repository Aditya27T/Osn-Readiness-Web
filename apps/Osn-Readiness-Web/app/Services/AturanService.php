<?php

namespace App\Services;

use App\Contracts\Repositories\AturanPemetaanRepositoryInterface;
use App\Contracts\Services\AturanServiceInterface;
use App\DTOs\AturanTingkat;
use RuntimeException;

class AturanService implements AturanServiceInterface
{
    /** @var array<int, AturanTingkat> */
    private array $cache = [];

    public function __construct(
        private readonly AturanPemetaanRepositoryInterface $aturanRepository,
    ) {}

    public function untukTingkat(int $tingkatId): AturanTingkat
    {
        return $this->cache[$tingkatId] ??= $this->bangun($tingkatId);
    }

    private function bangun(int $tingkatId): AturanTingkat
    {
        $rows = $this->aturanRepository->untukTingkat($tingkatId)->keyBy('parameter');

        $ambil = function (string $parameter) use ($rows, $tingkatId): float {
            if (! $rows->has($parameter)) {
                throw new RuntimeException(
                    "Aturan pemetaan tingkat {$tingkatId} tidak lengkap: parameter {$parameter} tidak ada."
                );
            }

            return (float) $rows[$parameter]->ketentuan;
        };

        return new AturanTingkat(
            tingkatId: $tingkatId,
            bobotMudah: (int) $ambil('bobot_mudah'),
            bobotSedang: (int) $ambil('bobot_sedang'),
            bobotSulit: (int) $ambil('bobot_sulit'),
            pretestJumlahSoal: (int) $ambil('pretest_jumlah_soal'),
            persenLevelPretest: [
                'mudah' => $ambil('pretest_persen_mudah'),
                'sedang' => $ambil('pretest_persen_sedang'),
                'sulit' => $ambil('pretest_persen_sulit'),
            ],
            pretestMinSoalPerMateri: (int) $ambil('pretest_min_soal_per_materi'),
            jumlahMateriWajib: (int) $ambil('jumlah_materi_wajib'),
            latihanMinSoal: (int) $ambil('latihan_min_soal'),
            latihanMinNilai: $ambil('latihan_min_nilai'),
            persenLevelSimulasi: [
                'mudah' => $ambil('simulasi_persen_mudah'),
                'sedang' => $ambil('simulasi_persen_sedang'),
                'sulit' => $ambil('simulasi_persen_sulit'),
            ],
            simulasiMaksPercobaan: (int) $ambil('simulasi_maks_percobaan'),
            passingGrade: $ambil('passing_grade'),
        );
    }
}
