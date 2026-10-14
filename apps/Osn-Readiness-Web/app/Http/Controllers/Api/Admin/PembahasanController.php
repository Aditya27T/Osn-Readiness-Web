<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\SoalAdminServiceInterface;
use App\Http\Requests\UpsertPembahasanRequest;
use App\Http\Resources\PembahasanResource;
use App\Models\Soal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PembahasanController
{
    public function __construct(
        private readonly SoalAdminServiceInterface $soalService,
    ) {}

    public function show(Soal $soal): JsonResponse
    {
        Gate::authorize('view', $soal);

        $pembahasan = $soal->pembahasan;

        if (! $pembahasan) {
            return response()->json([
                'message' => 'Pembahasan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'OK',
            'data' => new PembahasanResource($pembahasan),
        ]);
    }

    public function store(UpsertPembahasanRequest $request, Soal $soal): JsonResponse
    {
        Gate::authorize('update', $soal);

        $pembahasan = $this->soalService->simpanPembahasan($soal, $request->validated('isi_pembahasan'));

        return response()->json([
            'message' => 'Pembahasan berhasil disimpan',
            'data' => new PembahasanResource($pembahasan),
        ], 201);
    }

    public function update(UpsertPembahasanRequest $request, Soal $soal): JsonResponse
    {
        Gate::authorize('update', $soal);

        if (! $soal->pembahasan) {
            return response()->json([
                'message' => 'Pembahasan tidak ditemukan',
            ], 404);
        }

        $pembahasan = $this->soalService->simpanPembahasan($soal, $request->validated('isi_pembahasan'));

        return response()->json([
            'message' => 'Pembahasan berhasil diperbarui',
            'data' => new PembahasanResource($pembahasan),
        ]);
    }

    public function destroy(Soal $soal): JsonResponse
    {
        Gate::authorize('update', $soal);

        if (! $this->soalService->hapusPembahasan($soal)) {
            return response()->json([
                'message' => 'Pembahasan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Pembahasan berhasil dihapus',
        ]);
    }
}
