<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\HasilSimulasiJawabanRepositoryInterface;
use App\Models\HasilSimulasiJawaban;
use Illuminate\Database\Eloquent\Collection;

class HasilSimulasiJawabanRepository implements HasilSimulasiJawabanRepositoryInterface
{
    public function __construct(
        private readonly HasilSimulasiJawaban $model,
    ) {}

    public function untukHasil(int $hasilId): Collection
    {
        return $this->model->newQuery()
            ->where('hasil_simulasi_id', $hasilId)
            ->with('soal')
            ->orderBy('urutan')
            ->get();
    }

    public function findJawaban(int $hasilId, int $soalId): ?HasilSimulasiJawaban
    {
        return $this->model->newQuery()
            ->where('hasil_simulasi_id', $hasilId)
            ->where('soal_id', $soalId)
            ->first();
    }

    public function soalIdsTerpakaiPutaran(int $pretestId): array
    {
        return $this->model->newQuery()
            ->whereHas('hasilSimulasi', function ($query) use ($pretestId): void {
                $query->where('pretest_id', $pretestId);
            })
            ->distinct()
            ->pluck('soal_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function buatBanyak(int $hasilId, array $butir): void
    {
        $waktu = now();

        $baris = [];

        foreach ($butir as $butirSoal) {
            $baris[] = [
                'hasil_simulasi_id' => $hasilId,
                'soal_id' => $butirSoal['soal_id'],
                'urutan' => $butirSoal['urutan'],
                'bobot' => $butirSoal['bobot'],
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ];
        }

        $this->model->newQuery()->insert($baris);
    }
}
