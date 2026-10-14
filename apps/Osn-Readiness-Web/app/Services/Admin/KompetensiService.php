<?php

namespace App\Services\Admin;

use App\Contracts\Services\KompetensiServiceInterface;
use App\Exceptions\KompetensiMasihDigunakanException;
use App\Models\Kompetensi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class KompetensiService implements KompetensiServiceInterface
{
    /**
     * Get all kompetensi
     */
    public function getAll(): Collection
    {
        return Kompetensi::all();
    }

    /**
     * Get kompetensi by id
     */
    public function getById(int $id): Kompetensi
    {
        return Kompetensi::findOrFail($id);
    }

    /**
     * Create new kompetensi
     */
    public function create(array $data): Kompetensi
    {
        return Kompetensi::create($data);
    }

    /**
     * Update kompetensi
     */
    public function update(int $id, array $data): Kompetensi
    {
        $kompetensi = $this->getById($id);
        $kompetensi->update($data);

        return $kompetensi;
    }

    /**
     * Soft delete kompetensi (throw if has materi)
     *
     * @throws KompetensiMasihDigunakanException
     */
    public function delete(int $id): bool
    {
        $kompetensi = $this->getById($id);

        if (DB::table('materi')->where('kompetensi_id', $kompetensi->id)->exists()) {
            throw new KompetensiMasihDigunakanException;
        }

        return $kompetensi->delete();
    }
}
