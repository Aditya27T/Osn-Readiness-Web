<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\ProgressBelajarRepositoryInterface;
use App\Models\ProgressBelajar;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProgressBelajarRepository implements ProgressBelajarRepositoryInterface
{
    public function __construct(
        private readonly ProgressBelajar $model,
    ) {}

    public function untukUserDiTingkat(User $user, int $tingkatId): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->whereIn('materi_id', function ($query) use ($tingkatId): void {
                $query->select('id')->from('materi')->where('tingkat_id', $tingkatId);
            })
            ->get()
            ->keyBy('materi_id');
    }

    public function find(User $user, int $materiId): ?ProgressBelajar
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->where('materi_id', $materiId)
            ->first();
    }

    public function simpan(User $user, int $materiId, array $data): ProgressBelajar
    {
        return $this->model->newQuery()->updateOrCreate(
            ['user_id' => $user->id, 'materi_id' => $materiId],
            $data,
        );
    }

    public function hapusUntukMateri(User $user, array $materiIds): void
    {
        $this->model->newQuery()
            ->where('user_id', $user->id)
            ->whereIn('materi_id', $materiIds)
            ->delete();
    }
}
