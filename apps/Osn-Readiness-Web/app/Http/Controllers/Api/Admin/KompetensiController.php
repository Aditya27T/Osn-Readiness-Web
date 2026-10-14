<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\KompetensiServiceInterface;
use App\Http\Requests\Admin\Kompetensi\StoreKompetensiRequest;
use App\Http\Requests\Admin\Kompetensi\UpdateKompetensiRequest;
use App\Http\Resources\KompetensiResource;
use App\Models\Kompetensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class KompetensiController
{
    public function __construct(
        private readonly KompetensiServiceInterface $kompetensiService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Kompetensi::class);

        return KompetensiResource::collection($this->kompetensiService->getAll());
    }

    public function show(Kompetensi $kompetensi): JsonResponse
    {
        Gate::authorize('view', $kompetensi);

        return response()->json([
            'message' => 'OK',
            'data' => new KompetensiResource($kompetensi),
        ]);
    }

    public function store(StoreKompetensiRequest $request): JsonResponse
    {
        Gate::authorize('create', Kompetensi::class);

        $kompetensi = $this->kompetensiService->create($request->validated());

        return response()->json([
            'message' => 'Kompetensi berhasil ditambahkan',
            'data' => new KompetensiResource($kompetensi),
        ], 201);
    }

    public function update(UpdateKompetensiRequest $request, Kompetensi $kompetensi): JsonResponse
    {
        Gate::authorize('update', $kompetensi);

        $updated = $this->kompetensiService->update($kompetensi->id, $request->validated());

        return response()->json([
            'message' => 'Kompetensi berhasil diperbarui',
            'data' => new KompetensiResource($updated),
        ]);
    }

    public function destroy(Kompetensi $kompetensi): JsonResponse
    {
        Gate::authorize('delete', $kompetensi);

        $this->kompetensiService->delete($kompetensi->id);

        return response()->json([
            'message' => 'Kompetensi berhasil dihapus',
        ]);
    }
}
