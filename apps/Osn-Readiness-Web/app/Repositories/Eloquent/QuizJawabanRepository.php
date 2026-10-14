<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\QuizJawabanRepositoryInterface;
use App\Models\QuizJawaban;
use Illuminate\Database\Eloquent\Collection;

class QuizJawabanRepository implements QuizJawabanRepositoryInterface
{
    public function __construct(
        private readonly QuizJawaban $model,
    ) {}

    public function untukPengerjaan(int $pengerjaanId): Collection
    {
        return $this->model->newQuery()
            ->where('pengerjaan_id', $pengerjaanId)
            ->with('soal')
            ->orderBy('urutan')
            ->get();
    }

    public function findJawaban(int $pengerjaanId, int $soalId): ?QuizJawaban
    {
        return $this->model->newQuery()
            ->where('pengerjaan_id', $pengerjaanId)
            ->where('soal_id', $soalId)
            ->first();
    }

    public function soalIdsTerpakai(int $userId, int $tingkatId): array
    {
        return $this->model->newQuery()
            ->whereHas('pengerjaan', function ($query) use ($userId, $tingkatId): void {
                $query->where('user_id', $userId)->whereHas('quiz', function ($quiz) use ($tingkatId): void {
                    $quiz->whereHas('materi', function ($materi) use ($tingkatId): void {
                        $materi->where('tingkat_id', $tingkatId);
                    });
                });
            })
            ->distinct()
            ->pluck('soal_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function buatBanyak(int $pengerjaanId, array $butir): void
    {
        $waktu = now();

        $baris = [];

        foreach ($butir as $butirSoal) {
            $baris[] = [
                'pengerjaan_id' => $pengerjaanId,
                'soal_id' => $butirSoal['soal_id'],
                'urutan' => $butirSoal['urutan'],
                'bobot' => $butirSoal['bobot'],
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ];
        }

        // Satu INSERT, bukan satu per butir: pemanggilnya membungkus
        // semuanya dalam satu transaksi.
        $this->model->newQuery()->insert($baris);
    }
}
