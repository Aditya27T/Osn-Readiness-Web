<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\SimulasiServiceInterface;
use App\Http\Requests\Simulasi\SimpanJawabanRequest;
use App\Http\Requests\Simulasi\SubmitRequest;
use App\Http\Resources\HasilSimulasiResource;
use App\Http\Resources\HasilSimulasiReviewResource;
use App\Http\Resources\SimulasiPengerjaanResource;
use App\Models\HasilSimulasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HasilSimulasiController
{
    public function __construct(
        private readonly SimulasiServiceInterface $simulasiService,
    ) {}

    public function show(Request $request, HasilSimulasi $hasil): JsonResponse
    {
        $dimulai = $this->simulasiService->ringkasan($request->user(), $hasil->id);

        // Sudah dinilai: balas hasil, bukan daftar soal.
        if ($dimulai->hasil->selesai_pada !== null) {
            return response()->json([
                'message' => 'OK',
                'data' => new HasilSimulasiResource($dimulai->hasil->load('jawaban')),
            ]);
        }

        return response()->json([
            'message' => 'OK',
            'data' => new SimulasiPengerjaanResource($dimulai),
        ]);
    }

    public function simpanJawaban(SimpanJawabanRequest $request, HasilSimulasi $hasil): JsonResponse
    {
        $this->simulasiService->simpanJawaban(
            $request->user(),
            $hasil->id,
            $request->integer('soal_id'),
            $request->input('jawaban_user'),
        );

        return response()->json(['message' => 'Jawaban tersimpan']);
    }

    public function submit(SubmitRequest $request, HasilSimulasi $hasil): JsonResponse
    {
        $selesai = $this->simulasiService->submit($request->user(), $hasil->id);

        return response()->json([
            'message' => 'OK',
            'data' => new HasilSimulasiResource($selesai->load('jawaban')),
        ]);
    }

    public function review(Request $request, HasilSimulasi $hasil): JsonResponse
    {
        $selesai = $this->simulasiService->review($request->user(), $hasil->id);

        return response()->json([
            'message' => 'OK',
            'data' => new HasilSimulasiReviewResource($selesai->load('jawaban')),
        ]);
    }
}
