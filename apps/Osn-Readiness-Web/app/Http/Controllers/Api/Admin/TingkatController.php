<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\TingkatServiceAdminInterface;
use App\Http\Requests\Admin\Tingkat\UpdateTingkatRequest;
use App\Http\Resources\TingkatSeleksiResource;
use App\Models\TingkatSeleksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TingkatController
{
    public function __construct(
        private readonly TingkatServiceAdminInterface $tingkatService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', TingkatSeleksi::class);

        return TingkatSeleksiResource::collection($this->tingkatService->getAll());
    }

    public function show(TingkatSeleksi $tingkat): JsonResponse
    {
        Gate::authorize('view', $tingkat);

        return response()->json([
            'message' => 'OK',
            'data' => new TingkatSeleksiResource($tingkat),
        ]);
    }

    public function update(UpdateTingkatRequest $request, TingkatSeleksi $tingkat): JsonResponse
    {
        Gate::authorize('update', $tingkat);

        $updated = $this->tingkatService->update($tingkat->id, $request->validated());

        return response()->json([
            'message' => 'Tingkat berhasil diperbarui',
            'data' => new TingkatSeleksiResource($updated),
        ]);
    }
}
