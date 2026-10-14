<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\RekomendasiMateriRepositoryInterface;
use App\Models\RekomendasiMateri;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class RekomendasiMateriRepository extends BaseRepository implements RekomendasiMateriRepositoryInterface
{
    public function __construct(RekomendasiMateri $model)
    {
        parent::__construct($model);
    }

    public function buatBanyak(int $pretestId, int $userId, array $baris): void
    {
        $waktu = now();

        foreach ($baris as $barisSatu) {
            // Upsert, bukan insert: penilaian ulang untuk pre-test yang sama
            // (misalnya job NilaiUlangJob yang berjalan dua kali) tidak boleh
            // menabrak unique constraint pretest_id + materi_id.
            $this->model->newQuery()->updateOrCreate(
                [
                    'pretest_id' => $pretestId,
                    'materi_id' => $barisSatu['materi_id'],
                ],
                [
                    'user_id' => $userId,
                    'prioritas' => $barisSatu['prioritas'],
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ],
            );
        }
    }

    public function untukPretest(int $pretestId): Collection
    {
        return $this->model->newQuery()
            ->with('materi.quiz')
            ->where('pretest_id', $pretestId)
            ->orderBy('prioritas')
            ->get();
    }
}
