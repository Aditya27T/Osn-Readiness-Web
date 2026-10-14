<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\PemetaanMateriRepositoryInterface;
use App\Models\PemetaanMateri;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class PemetaanMateriRepository extends BaseRepository implements PemetaanMateriRepositoryInterface
{
    public function __construct(PemetaanMateri $model)
    {
        parent::__construct($model);
    }

    public function buatBanyak(int $pretestId, int $userId, array $baris): void
    {
        $waktu = now();

        foreach ($baris as $barisSatu) {
            // Upsert dengan alasan yang sama seperti RekomendasiMateriRepository:
            // penilaian ulang untuk pre-test yang sama harus aman.
            $this->model->newQuery()->updateOrCreate(
                [
                    'pretest_id' => $pretestId,
                    'materi_id' => $barisSatu['materi_id'],
                ],
                [
                    'user_id' => $userId,
                    'jumlah_soal' => $barisSatu['jumlah_soal'],
                    'jumlah_benar' => $barisSatu['jumlah_benar'],
                    'poin_didapat' => $barisSatu['poin_didapat'],
                    'poin_maksimal' => $barisSatu['poin_maksimal'],
                    'persentase' => $barisSatu['persentase'],
                    'peringkat' => $barisSatu['peringkat'],
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ],
            );
        }
    }

    public function untukPretest(int $pretestId): Collection
    {
        return $this->model->newQuery()
            ->where('pretest_id', $pretestId)
            ->orderBy('peringkat')
            ->get();
    }
}
