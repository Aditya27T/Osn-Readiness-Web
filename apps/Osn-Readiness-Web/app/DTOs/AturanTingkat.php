<?php

namespace App\DTOs;

use App\Enums\Level;

/**
 * Aturan satu tingkat, dibaca dari tabel aturan_pemetaan dan dikonversi ke tipe.
 *
 * Angka disimpan sebagai string di database, jadi Service yang mengubahnya menjadi
 * int/float, bukan pemanggilnya.
 */
final class AturanTingkat
{
    /**
     * @param  array<string, float>  $persenLevelPretest  diindeks dengan nilai Level
     * @param  array<string, float>  $persenLevelSimulasi  diindeks dengan nilai Level
     */
    public function __construct(
        public readonly int $tingkatId,
        public readonly int $bobotMudah,
        public readonly int $bobotSedang,
        public readonly int $bobotSulit,
        public readonly int $pretestJumlahSoal,
        public readonly array $persenLevelPretest,
        public readonly int $pretestMinSoalPerMateri,
        public readonly int $jumlahMateriWajib,
        public readonly int $latihanMinSoal,
        public readonly float $latihanMinNilai,
        public readonly array $persenLevelSimulasi,
        public readonly int $simulasiMaksPercobaan,
        public readonly float $passingGrade,
    ) {}

    public function bobot(Level $level): int
    {
        return match ($level) {
            Level::Mudah => $this->bobotMudah,
            Level::Sedang => $this->bobotSedang,
            Level::Sulit => $this->bobotSulit,
        };
    }

    public function persenPretest(Level $level): float
    {
        return $this->persenLevelPretest[$level->value];
    }

    public function persenSimulasi(Level $level): float
    {
        return $this->persenLevelSimulasi[$level->value];
    }

    /**
     * Persen level sebagai array sederhana, urut mudah, sedang, sulit.
     *
     * @return array<int, float>
     */
    public function persenPretestBerurutan(): array
    {
        return [
            $this->persenPretest(Level::Mudah),
            $this->persenPretest(Level::Sedang),
            $this->persenPretest(Level::Sulit),
        ];
    }

    public function persenSimulasiBerurutan(): array
    {
        return [
            $this->persenSimulasi(Level::Mudah),
            $this->persenSimulasi(Level::Sedang),
            $this->persenSimulasi(Level::Sulit),
        ];
    }
}
