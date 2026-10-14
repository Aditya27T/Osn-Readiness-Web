<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\LatihanAdminServiceInterface;
use App\Http\Requests\StoreLatihanRequest;
use App\Http\Requests\UpdateLatihanRequest;
use App\Http\Resources\LatihanResource;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Nama argumen $latihan harus sama dengan parameter rute {latihan}; bila
 * berbeda, implicit binding tidak jalan dan yang masuk adalah Quiz kosong.
 */
class LatihanController
{
    public function __construct(
        private readonly LatihanAdminServiceInterface $latihanService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Quiz::class);

        $perPage = (int) request('per_page', 15);

        return LatihanResource::collection($this->latihanService->daftar($perPage));
    }

    public function show(Quiz $latihan): JsonResponse
    {
        Gate::authorize('view', $latihan);

        $latihan->load('materi');

        return response()->json([
            'message' => 'OK',
            'data' => new LatihanResource($latihan),
        ]);
    }

    public function store(StoreLatihanRequest $request): JsonResponse
    {
        Gate::authorize('create', Quiz::class);

        $latihan = $this->latihanService->buat($request->validated());
        $latihan->load('materi');

        return response()->json([
            'message' => 'Latihan berhasil ditambahkan',
            'data' => new LatihanResource($latihan),
        ], 201);
    }

    public function update(UpdateLatihanRequest $request, Quiz $latihan): JsonResponse
    {
        Gate::authorize('update', $latihan);

        $latihan = $this->latihanService->perbarui($latihan, $request->validated());
        $latihan->load('materi');

        return response()->json([
            'message' => 'Latihan berhasil diperbarui',
            'data' => new LatihanResource($latihan),
        ]);
    }

    public function destroy(Quiz $latihan): JsonResponse
    {
        Gate::authorize('delete', $latihan);

        $this->latihanService->hapus($latihan);

        return response()->json([
            'message' => 'Latihan berhasil dihapus',
        ]);
    }
}
