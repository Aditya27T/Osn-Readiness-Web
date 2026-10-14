<?php

namespace App\Contracts\Repositories;

use App\Models\AturanPemetaan;
use Illuminate\Database\Eloquent\Collection;

interface AturanPemetaanRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, AturanPemetaan>
     */
    public function untukTingkat(int $tingkatId): Collection;

    /**
     * Simpan atau perbarui kumpulan parameter milik satu tingkat sekaligus.
     *
     * @param  array<string, string>  $parameterKetentuan  parameter => ketentuan
     */
    public function simpanBanyak(int $tingkatId, array $parameterKetentuan): void;
}
