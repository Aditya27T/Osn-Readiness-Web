<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\UpdateAturanPemetaanRequest;
use App\Models\AturanPemetaan;
use App\Models\TingkatSeleksi;
use App\Services\Admin\AturanPemetaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AturanPemetaanController
{
    public function __construct(
        private readonly AturanPemetaanService $aturanPemetaanService,
    ) {}

    public function show(TingkatSeleksi $tingkat): JsonResponse
    {
        Gate::authorize('viewAny', AturanPemetaan::class);

        $flat = $this->aturanPemetaanService->perTingkat($tingkat->id);

        if ($flat === null) {
            return response()->json([
                'message' => 'Aturan pemetaan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'OK',
            'data' => ['tingkat_id' => $tingkat->id] + $flat,
        ]);
    }

    public function update(UpdateAturanPemetaanRequest $request, TingkatSeleksi $tingkat): JsonResponse
    {
        Gate::authorize('update', AturanPemetaan::class);

        if ($this->aturanPemetaanService->perTingkat($tingkat->id) === null) {
            return response()->json([
                'message' => 'Aturan pemetaan tidak ditemukan',
            ], 404);
        }

        $flat = $this->aturanPemetaanService->perbarui($tingkat, $request->validated());

        return response()->json([
            'message' => 'Aturan pemetaan berhasil diperbarui',
            'data' => ['tingkat_id' => $tingkat->id] + $flat,
        ]);
    }
}
