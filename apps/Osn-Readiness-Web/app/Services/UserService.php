<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    public function registerUser(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = $this->userRepository->create($data);
            $user->assignRole('siswa');

            return $user;
        });
    }

    public function updateUser(User $user, array $data): User
    {
        $updated = $this->userRepository->update($user, $data);

        // Menonaktifkan lewat edit harus langsung menutup akses, sama seperti
        // memanggil deactivateUser secara eksplisit.
        if (! $updated->is_active) {
            $this->revokeAllTokens($updated);
        }

        return $updated;
    }

    public function deactivateUser(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $updated = $this->userRepository->update($user, ['is_active' => false]);
            $this->revokeAllTokens($updated);

            return $updated;
        });
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            $this->revokeAllTokens($user);

            return $this->userRepository->delete($user);
        });
    }

    /**
     * Soft delete dan penonaktifan mencabut semua token supaya akun tidak bisa
     * dipakai lagi walau token-nya sudah dibagikan.
     */
    private function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
