<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\PretestJawabanRepositoryInterface;
use App\Models\PretestJawaban;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class PretestJawabanRepository extends BaseRepository implements PretestJawabanRepositoryInterface
{
    public function __construct(PretestJawaban $model)
    {
        parent::__construct($model);
    }

    public function untukPretest(int $pretestId): Collection
    {
        return $this->model->newQuery()
            ->with('soal')
            ->where('pretest_id', $pretestId)
            ->orderBy('urutan')
            ->get();
    }

    public function soalIdsTerpakai(int $userId, int $tingkatId): array
    {
        return $this->model->newQuery()
            ->join('pretest', 'pretest.id', '=', 'pretest_jawaban.pretest_id')
            ->where('pretest.user_id', $userId)
            ->where('pretest.tingkat_id', $tingkatId)
            ->pluck('pretest_jawaban.soal_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function buatBanyak(int $pretestId, array $butir): void
    {
        $waktu = now();

        $baris = [];

        foreach ($butir as $butirSatu) {
            $baris[] = [
                'pretest_id' => $pretestId,
                'soal_id' => $butirSatu['soal_id'],
                'urutan' => $butirSatu['urutan'],
                'bobot' => $butirSatu['bobot'],
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ];
        }

        // Satu INSERT, bukan satu per butir: pre-test bisa 30 soal dan
        // pemanggilnya membungkus semuanya dalam satu transaksi.
        $this->model->newQuery()->insert($baris);
    }

    public function findJawaban(int $pretestId, int $soalId): ?PretestJawaban
    {
        return $this->model->newQuery()
            ->where('pretest_id', $pretestId)
            ->where('soal_id', $soalId)
            ->first();
    }
}
