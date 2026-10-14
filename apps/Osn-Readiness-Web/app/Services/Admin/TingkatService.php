<?php

namespace App\Services\Admin;

use App\Contracts\Services\TingkatServiceAdminInterface;
use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Collection;

class TingkatService implements TingkatServiceAdminInterface
{
    /**
     * Get all tingkat
     */
    public function getAll(): Collection
    {
        return TingkatSeleksi::all();
    }

    /**
     * Get tingkat by id
     */
    public function getById(int $id): TingkatSeleksi
    {
        return TingkatSeleksi::findOrFail($id);
    }

    /**
     * Update tingkat (name and deskripsi only)
     */
    public function update(int $id, array $data): TingkatSeleksi
    {
        $tingkat = $this->getById($id);
        $tingkat->update($data);

        return $tingkat;
    }

    /**
     * Delete tingkat
     */
    public function delete(int $id): bool
    {
        return (bool) $this->getById($id)->delete();
    }
}
