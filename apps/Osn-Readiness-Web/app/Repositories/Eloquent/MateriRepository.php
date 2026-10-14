<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\MateriRepositoryInterface;
use App\Models\Materi;
use App\Models\PemetaanMateri;
use App\Models\ProgressBelajar;
use App\Models\Quiz;
use App\Models\RekomendasiMateri;
use App\Models\Soal;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class MateriRepository extends BaseRepository implements MateriRepositoryInterface
{
    public function __construct(Materi $model)
    {
        parent::__construct($model);
    }

    public function untukTingkat(int $tingkatId): Collection
    {
        return $this->model->newQuery()
            ->where('tingkat_id', $tingkatId)
            ->orderBy('urutan')
            ->get();
    }

    public function daftarAdmin(?int $kompetensiId = null): Collection
    {
        return $this->model->newQuery()
            ->when($kompetensiId !== null, fn ($query) => $query->where('kompetensi_id', $kompetensiId))
            ->orderBy('tingkat_id')
            ->orderBy('urutan')
            ->get();
    }

    public function alasanTidakBisaDihapus(Materi $materi): ?string
    {
        if (Soal::withTrashed()->where('materi_id', $materi->id)->exists()) {
            return 'Materi masih memiliki soal.';
        }

        if (Quiz::query()->where('materi_id', $materi->id)->exists()) {
            return 'Materi masih memiliki latihan.';
        }

        $dirujukHasil = PemetaanMateri::query()->where('materi_id', $materi->id)->exists()
            || RekomendasiMateri::query()->where('materi_id', $materi->id)->exists()
            || ProgressBelajar::query()->where('materi_id', $materi->id)->exists();

        return $dirujukHasil ? 'Materi masih dirujuk hasil siswa.' : null;
    }
}
