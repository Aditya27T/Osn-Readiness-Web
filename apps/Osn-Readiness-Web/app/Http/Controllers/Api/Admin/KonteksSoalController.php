<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\KonteksSoalServiceInterface;
use App\Http\Requests\Admin\KonteksSoal\StoreKonteksSoalRequest;
use App\Http\Requests\Admin\KonteksSoal\UpdateKonteksSoalRequest;
use App\Http\Resources\KonteksSoalResource;
use App\Models\KonteksSoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class KonteksSoalController
{
    public function __construct(
        private readonly KonteksSoalServiceInterface $konteksSoalService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', KonteksSoal::class);

        return KonteksSoalResource::collection($this->konteksSoalService->getAll());
    }

    public function show(KonteksSoal $konteksSoal): JsonResponse
    {
        Gate::authorize('view', $konteksSoal);

        return response()->json([
            'message' => 'OK',
            'data' => new KonteksSoalResource($konteksSoal),
        ]);
    }

    public function store(StoreKonteksSoalRequest $request): JsonResponse
    {
        Gate::authorize('create', KonteksSoal::class);

        $konteksSoal = $this->konteksSoalService->create($request->validated());

        return response()->json([
            'message' => 'Konteks Soal berhasil ditambahkan',
            'data' => new KonteksSoalResource($konteksSoal),
        ], 201);
    }

    public function update(UpdateKonteksSoalRequest $request, KonteksSoal $konteksSoal): JsonResponse
    {
        Gate::authorize('update', $konteksSoal);

        $updated = $this->konteksSoalService->update($konteksSoal->id, $request->validated());

        return response()->json([
            'message' => 'Konteks Soal berhasil diperbarui',
            'data' => new KonteksSoalResource($updated),
        ]);
    }

    public function destroy(KonteksSoal $konteksSoal): JsonResponse
    {
        Gate::authorize('delete', $konteksSoal);

        $this->konteksSoalService->delete($konteksSoal->id);

        return response()->json([
            'message' => 'Konteks Soal berhasil dihapus',
        ]);
    }
}
