<?php

namespace App\Services\Admin;

use App\Contracts\Services\KonteksSoalServiceInterface;
use App\Exceptions\KonteksSoalMasihDigunakanException;
use App\Models\KonteksSoal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class KonteksSoalService implements KonteksSoalServiceInterface
{
    /**
     * Get all konteks soal
     */
    public function getAll(): Collection
    {
        return KonteksSoal::all();
    }

    /**
     * Get konteks soal by id
     */
    public function getById(int $id): KonteksSoal
    {
        return KonteksSoal::findOrFail($id);
    }

    /**
     * Create new konteks soal
     */
    public function create(array $data): KonteksSoal
    {
        return KonteksSoal::create($data);
    }

    /**
     * Update konteks soal
     */
    public function update(int $id, array $data): KonteksSoal
    {
        $konteks = $this->getById($id);
        $konteks->update($data);

        return $konteks;
    }

    /**
     * Soft delete konteks soal (throw if used by soal)
     *
     * @throws KonteksSoalMasihDigunakanException
     */
    public function delete(int $id): bool
    {
        $konteks = $this->getById($id);

        if (DB::table('soal')->where('konteks_id', $konteks->id)->exists()) {
            throw new KonteksSoalMasihDigunakanException;
        }

        return $konteks->delete();
    }
}
