<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\SoalRepositoryInterface;
use App\Enums\Peruntukan;
use App\Models\HasilSimulasiJawaban;
use App\Models\Pembahasan;
use App\Models\PretestJawaban;
use App\Models\QuizJawaban;
use App\Models\Soal;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SoalRepository extends BaseRepository implements SoalRepositoryInterface
{
    public function __construct(Soal $model)
    {
        parent::__construct($model);
    }

    public function kandidat(int $tingkatId, Peruntukan $peruntukan, array $kecuali = [], ?int $materiId = null): Collection
    {
        return $this->model->newQuery()
            ->where('tingkat_id', $tingkatId)
            ->where('peruntukan', $peruntukan->value)
            // newQuery() sudah menerapkan soft delete, jadi soal yang
            // dihapus tidak ikut terambil.
            ->when($kecuali !== [], fn ($query) => $query->whereNotIn('id', $kecuali))
            ->when($materiId !== null, fn ($query) => $query->where('materi_id', $materiId))
            ->orderBy('id')
            ->get();
    }

    public function banyakDenganKonteks(array $ids): Collection
    {
        return $this->model->newQuery()
            ->with(['konteks', 'pembahasan'])
            // Memakai withTrashed karena soal yang sudah dipakai pengerjaan
            // tetap boleh ditampilkan di review meski di-soft delete admin.
            ->withTrashed()
            ->whereIn('id', $ids)
            ->get();
    }

    public function sedangDipakai(Soal $soal): bool
    {
        return PretestJawaban::query()->where('soal_id', $soal->id)->exists()
            || QuizJawaban::query()->where('soal_id', $soal->id)->exists()
            || HasilSimulasiJawaban::query()->where('soal_id', $soal->id)->exists();
    }

    public function simpanPembahasan(Soal $soal, string $isi): Pembahasan
    {
        /** @var Pembahasan */
        return Pembahasan::query()->updateOrCreate(
            ['soal_id' => $soal->id],
            ['isi_pembahasan' => $isi],
        );
    }

    public function hapusPembahasan(Soal $soal): bool
    {
        return Pembahasan::query()->where('soal_id', $soal->id)->delete() > 0;
    }

    public function paginasiAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['tingkat', 'materi', 'konteks', 'pembahasan'])
            ->latest()
            ->paginate($perPage);
    }
}
