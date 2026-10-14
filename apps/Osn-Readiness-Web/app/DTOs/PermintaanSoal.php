<?php

namespace App\DTOs;

use App\Enums\Peruntukan;

/**
 * Permintaan pengambilan soal.
 *
 * Setiap jenis pengerjaan mengisi isinya sendiri: pre-test mengirim persen
 * level dan batas materi, latihan hanya mengirim satu materi tanpa pembagian
 * level, simulasi mengirim persen level dan daftar soal yang dihindari.
 */
final class PermintaanSoal
{
    /**
     * @param  array<string, float>  $persenLevel  diindeks dengan nilai Level; kosong berarti bebas tanpa pembagian level
     * @param  int  $minimalSoalPerMateri  batas minimal per materi, 0 berarti tidak ada batas
     * @param  int|null  $materiId  membatasi kandidat pada satu materi, dipakai latihan
     * @param  array<int, int>  $soalDikecualikan  soal yang tidak boleh dipakai lagi sama sekali
     * @param  array<int, int>  $soalDihindari  soal yang dipakai terakhir kali bila kandidat lain habis
     */
    public function __construct(
        public readonly int $tingkatId,
        public readonly Peruntukan $peruntukan,
        public readonly int $jumlahSoal,
        public readonly array $persenLevel = [],
        public readonly int $minimalSoalPerMateri = 0,
        public readonly ?int $materiId = null,
        public readonly array $soalDikecualikan = [],
        public readonly array $soalDihindari = [],
    ) {}

    public function pakaiBatasMateri(): bool
    {
        return $this->minimalSoalPerMateri > 0 && $this->materiId === null;
    }

    public function tanpaPembagianLevel(): bool
    {
        return $this->persenLevel === [];
    }

    public function menghindari(): bool
    {
        return $this->soalDihindari !== [];
    }
}
