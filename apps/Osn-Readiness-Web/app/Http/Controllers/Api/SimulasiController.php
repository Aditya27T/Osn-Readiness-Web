<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\SimulasiServiceInterface;
use App\Http\Requests\Simulasi\IndexRequest;
use App\Http\Resources\SimulasiPengerjaanResource;
use App\Models\Simulasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimulasiController
{
    public function __construct(
        private readonly SimulasiServiceInterface $simulasiService,
    ) {}

    public function index(IndexRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => $this->simulasiService->daftar($request->user(), $request->integer('tingkat_id')),
        ]);
    }

    public function mulai(Request $request, Simulasi $simulasi): JsonResponse
    {
        $dimulai = $this->simulasiService->mulai($request->user(), $simulasi);

        // Percobaan baru dibalas 201; yang sedang berjalan dikembalikan
        // ulang dengan 200 supaya siswa menyambung, bukan mengulang.
        return response()->json([
            'message' => 'OK',
            'data' => new SimulasiPengerjaanResource($dimulai),
        ], $dimulai->baru ? 201 : 200);
    }
}
