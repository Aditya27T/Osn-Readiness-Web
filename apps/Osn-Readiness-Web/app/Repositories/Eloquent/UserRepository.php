<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        // Tanpa membedakan huruf besar dan kecil; akun terhapus tidak ikut.
        return $this->model->newQuery()->whereRaw('lower(email) = ?', [strtolower($email)])->first();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->with('roles')->latest()->paginate($perPage);
    }

    public function paginasiSiswa(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->role('siswa')->with('roles')->latest()->paginate($perPage);
    }

    public function cariSiswa(int $id): ?User
    {
        return $this->model->newQuery()->role('siswa')->find($id);
    }
}
