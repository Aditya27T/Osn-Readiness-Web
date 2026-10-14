<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\SoalAdminServiceInterface;
use App\Http\Requests\StoreSoalRequest;
use App\Http\Requests\UpdateSoalRequest;
use App\Http\Resources\SoalDetailResource;
use App\Models\Soal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SoalController
{
    public function __construct(
        private readonly SoalAdminServiceInterface $soalService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Soal::class);

        $perPage = (int) request('per_page', 15);

        return SoalDetailResource::collection($this->soalService->daftar($perPage));
    }

    public function show(Soal $soal): JsonResponse
    {
        Gate::authorize('view', $soal);

        $soal->load('tingkat', 'materi', 'konteks', 'pembahasan');

        return response()->json([
            'message' => 'OK',
            'data' => new SoalDetailResource($soal),
        ]);
    }

    public function store(StoreSoalRequest $request): JsonResponse
    {
        Gate::authorize('create', Soal::class);

        $soal = $this->soalService->buat($request->validated(), $request->file('gambar'));
        $soal->load('tingkat', 'materi', 'konteks', 'pembahasan');

        return response()->json([
            'message' => 'Soal berhasil ditambahkan',
            'data' => new SoalDetailResource($soal),
        ], 201);
    }

    public function update(UpdateSoalRequest $request, Soal $soal): JsonResponse
    {
        Gate::authorize('update', $soal);

        $soal = $this->soalService->perbarui($soal, $request->validated(), $request->file('gambar'));
        $soal->load('tingkat', 'materi', 'konteks', 'pembahasan');

        return response()->json([
            'message' => 'Soal berhasil diperbarui',
            'data' => new SoalDetailResource($soal),
        ]);
    }

    public function destroy(Soal $soal): JsonResponse
    {
        Gate::authorize('delete', $soal);

        $this->soalService->hapus($soal);

        return response()->json([
            'message' => 'Soal berhasil dihapus',
        ]);
    }
}
