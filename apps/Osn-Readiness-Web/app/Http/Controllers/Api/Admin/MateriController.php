<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\MateriServiceInterface;
use App\Http\Requests\Admin\Materi\StoreMateriRequest;
use App\Http\Requests\Admin\Materi\UpdateMateriRequest;
use App\Http\Requests\Admin\Materi\UploadMateriImageRequest;
use App\Http\Resources\MateriResource;
use App\Models\Materi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class MateriController
{
    public function __construct(
        private readonly MateriServiceInterface $materiService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Materi::class);

        $kompetensiId = request('kompetensi_id');

        return MateriResource::collection($this->materiService->getAll($kompetensiId));
    }

    public function show(Materi $materi): JsonResponse
    {
        Gate::authorize('view', $materi);

        return response()->json([
            'message' => 'OK',
            'data' => new MateriResource($materi),
        ]);
    }

    public function store(StoreMateriRequest $request): JsonResponse
    {
        Gate::authorize('create', Materi::class);

        $materi = $this->materiService->create($request->validated());

        return response()->json([
            'message' => 'Materi berhasil ditambahkan',
            'data' => new MateriResource($materi),
        ], 201);
    }

    public function update(UpdateMateriRequest $request, Materi $materi): JsonResponse
    {
        Gate::authorize('update', $materi);

        $updated = $this->materiService->update($materi->id, $request->validated());

        return response()->json([
            'message' => 'Materi berhasil diperbarui',
            'data' => new MateriResource($updated),
        ]);
    }

    public function destroy(Materi $materi): JsonResponse
    {
        Gate::authorize('delete', $materi);

        $this->materiService->delete($materi->id);

        return response()->json([
            'message' => 'Materi berhasil dihapus',
        ]);
    }

    public function uploadGambar(UploadMateriImageRequest $request): JsonResponse
    {
        Gate::authorize('create', Materi::class);

        return response()->json([
            'message' => 'Gambar berhasil diunggah',
            'data' => ['path' => $this->materiService->simpanGambar($request->file('gambar'))],
        ], 201);
    }
}
