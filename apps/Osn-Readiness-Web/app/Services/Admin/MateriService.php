<?php

namespace App\Services\Admin;

use App\Contracts\Repositories\MateriRepositoryInterface;
use App\Contracts\Services\MateriServiceInterface;
use App\Exceptions\MateriMasihDigunakanException;
use App\Models\Materi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class MateriService implements MateriServiceInterface
{
    public function __construct(
        private readonly MateriRepositoryInterface $materiRepository,
    ) {}

    public function getAll(?int $kompetensiId = null): Collection
    {
        return $this->materiRepository->daftarAdmin($kompetensiId);
    }

    public function getById(int $id): Materi
    {
        /** @var Materi */
        return $this->materiRepository->findOrFail($id);
    }

    public function create(array $data): Materi
    {
        /** @var Materi */
        return $this->materiRepository->create($data);
    }

    public function update(int $id, array $data): Materi
    {
        /** @var Materi */
        return $this->materiRepository->update($this->getById($id), $data);
    }

    /**
     * @throws MateriMasihDigunakanException
     */
    public function delete(int $id): bool
    {
        $materi = $this->getById($id);

        $alasan = $this->materiRepository->alasanTidakBisaDihapus($materi);

        if ($alasan !== null) {
            throw new MateriMasihDigunakanException($alasan);
        }

        return $this->materiRepository->delete($materi);
    }

    public function simpanGambar(UploadedFile $gambar): string
    {
        // Sama dengan alamat yang ditulis importer ke isi_materi: tanpa nama
        // domain, supaya tetap benar bila domain berganti (BE-21).
        return '/storage/'.$gambar->store('materi', 'public');
    }
}
