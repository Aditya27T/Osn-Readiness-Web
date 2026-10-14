<?php

namespace App\Services\Admin;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\SiswaServiceInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SiswaService implements SiswaServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserServiceInterface $userService,
    ) {}

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginasiSiswa($perPage);
    }

    public function getById(int $id): User
    {
        $siswa = $this->userRepository->cariSiswa($id);

        // Akun non-siswa dianggap tidak ada, sehingga admin tidak bisa
        // menonaktifkan atau menghapus dirinya sendiri maupun admin lain
        // lewat menu siswa.
        if (! $siswa instanceof User) {
            throw (new ModelNotFoundException)->setModel(User::class, [$id]);
        }

        return $siswa;
    }

    public function update(int $id, array $data): User
    {
        return $this->userService->updateUser($this->getById($id), $data);
    }

    public function deactivate(int $id): User
    {
        return $this->userService->deactivateUser($this->getById($id));
    }

    public function delete(int $id): void
    {
        $this->userService->deleteUser($this->getById($id));
    }
}
