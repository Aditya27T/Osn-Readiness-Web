<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\SimulasiAdminServiceInterface;
use App\Http\Requests\StoreSimulasiRequest;
use App\Http\Requests\UpdateSimulasiRequest;
use App\Http\Resources\SimulasiResource;
use App\Models\Simulasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SimulasiController
{
    public function __construct(
        private readonly SimulasiAdminServiceInterface $simulasiService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Simulasi::class);

        $perPage = (int) request('per_page', 15);

        return SimulasiResource::collection($this->simulasiService->daftar($perPage));
    }

    public function show(Simulasi $simulasi): JsonResponse
    {
        Gate::authorize('view', $simulasi);

        $simulasi->load('tingkat');

        return response()->json([
            'message' => 'OK',
            'data' => new SimulasiResource($simulasi),
        ]);
    }

    public function store(StoreSimulasiRequest $request): JsonResponse
    {
        Gate::authorize('create', Simulasi::class);

        $simulasi = $this->simulasiService->buat($request->validated());
        $simulasi->load('tingkat');

        return response()->json([
            'message' => 'Simulasi berhasil ditambahkan',
            'data' => new SimulasiResource($simulasi),
        ], 201);
    }

    public function update(UpdateSimulasiRequest $request, Simulasi $simulasi): JsonResponse
    {
        Gate::authorize('update', $simulasi);

        $simulasi = $this->simulasiService->perbarui($simulasi, $request->validated());
        $simulasi->load('tingkat');

        return response()->json([
            'message' => 'Simulasi berhasil diperbarui',
            'data' => new SimulasiResource($simulasi),
        ]);
    }

    public function destroy(Simulasi $simulasi): JsonResponse
    {
        Gate::authorize('delete', $simulasi);

        $this->simulasiService->hapus($simulasi);

        return response()->json([
            'message' => 'Simulasi berhasil dihapus',
        ]);
    }
}
