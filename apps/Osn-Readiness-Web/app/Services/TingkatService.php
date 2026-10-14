<?php

namespace App\Services;

use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Services\PutaranServiceInterface;
use App\Contracts\Services\TingkatServiceInterface;
use App\DTOs\TingkatSiswa;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Support\Collection;

class TingkatService implements TingkatServiceInterface
{
    public function __construct(
        private readonly TingkatSeleksiRepositoryInterface $tingkatRepository,
        private readonly PutaranServiceInterface $putaranService,
    ) {}

    public function daftar(User $user): Collection
    {
        return $this->tingkatRepository->semuaTerurut()
            ->map(fn (TingkatSeleksi $tingkat): TingkatSiswa => new TingkatSiswa(
                tingkat: $tingkat,
                status: $this->putaranService->status($user, $tingkat),
            ));
    }
}
