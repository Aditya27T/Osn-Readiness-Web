<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\KenaikanTingkatRepositoryInterface;
use App\Enums\StatusKenaikan;
use App\Models\KenaikanTingkat;
use App\Repositories\BaseRepository;

class KenaikanTingkatRepository extends BaseRepository implements KenaikanTingkatRepositoryInterface
{
    public function __construct(KenaikanTingkat $model)
    {
        parent::__construct($model);
    }

    public function adaLulus(int $userId, int $tingkatId): bool
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('tingkat_asal_id', $tingkatId)
            ->where('status', StatusKenaikan::Lulus->value)
            ->exists();
    }

    public function catatLulus(int $userId, int $tingkatAsalId, ?int $tingkatTujuanId): void
    {
        // ON CONFLICT DO NOTHING: insert kembar tidak membatalkan transaksi
        // Postgres seperti yang terjadi bila unique violation ditangkap.
        $this->model->newQuery()->insertOrIgnore([
            'user_id' => $userId,
            'tingkat_asal_id' => $tingkatAsalId,
            'tingkat_tujuan_id' => $tingkatTujuanId,
            'status' => StatusKenaikan::Lulus->value,
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function catatTidakLulus(int $userId, int $tingkatAsalId, string $keterangan): void
    {
        $this->model->newQuery()->create([
            'user_id' => $userId,
            'tingkat_asal_id' => $tingkatAsalId,
            'tingkat_tujuan_id' => null,
            'status' => StatusKenaikan::TidakLulus->value,
            'keterangan' => $keterangan,
        ]);
    }
}
