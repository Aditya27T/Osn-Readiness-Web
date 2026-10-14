<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Services\PutaranServiceInterface;
use App\Contracts\Services\SyaratSimulasiServiceInterface;
use App\Exceptions\TingkatTerkunciException;
use App\Http\Requests\TingkatWajibRequest;
use App\Http\Resources\SyaratSimulasiResource;
use App\Models\TingkatSeleksi;

class SyaratSimulasiController
{
    public function __construct(
        private readonly SyaratSimulasiServiceInterface $syaratSimulasiService,
        private readonly PutaranServiceInterface $putaranService,
        private readonly TingkatSeleksiRepositoryInterface $tingkatRepository,
    ) {}

    public function show(TingkatWajibRequest $request): SyaratSimulasiResource
    {
        /** @var TingkatSeleksi $tingkat */
        $tingkat = $this->tingkatRepository->findOrFail($request->integer('tingkat_id'));

        // Sama seperti endpoint materi dan latihan: tingkat terkunci 403.
        if (! $this->putaranService->status($request->user(), $tingkat)->tingkatTerbuka) {
            throw new TingkatTerkunciException("Tingkat {$tingkat->nama_tingkat} belum terbuka.");
        }

        return new SyaratSimulasiResource(
            $this->syaratSimulasiService->periksa($request->user(), $tingkat)
        );
    }
}
