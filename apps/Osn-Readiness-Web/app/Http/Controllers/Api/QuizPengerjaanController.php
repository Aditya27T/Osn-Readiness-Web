<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\LatihanServiceInterface;
use App\Http\Requests\Latihan\SimpanJawabanRequest;
use App\Http\Requests\Latihan\SubmitRequest;
use App\Http\Resources\HasilLatihanResource;
use App\Http\Resources\LatihanPengerjaanResource;
use App\Models\QuizPengerjaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizPengerjaanController
{
    public function __construct(
        private readonly LatihanServiceInterface $latihanService,
    ) {}

    public function show(Request $request, QuizPengerjaan $pengerjaan): JsonResponse
    {
        $dimulai = $this->latihanService->ringkasan($request->user(), $pengerjaan->id);

        // Sudah dinilai: balas hasil, sama seperti pre-test dan simulasi.
        if ($dimulai->pengerjaan->selesai_pada !== null) {
            return response()->json([
                'message' => 'OK',
                'data' => new HasilLatihanResource($dimulai->pengerjaan),
            ]);
        }

        return response()->json([
            'message' => 'OK',
            'data' => new LatihanPengerjaanResource($dimulai),
        ]);
    }

    public function simpanJawaban(SimpanJawabanRequest $request, QuizPengerjaan $pengerjaan): JsonResponse
    {
        $this->latihanService->simpanJawaban(
            $request->user(),
            $pengerjaan->id,
            $request->integer('soal_id'),
            $request->input('jawaban_user'),
        );

        return response()->json(['message' => 'Jawaban tersimpan']);
    }

    public function submit(SubmitRequest $request, QuizPengerjaan $pengerjaan): JsonResponse
    {
        $selesai = $this->latihanService->submit($request->user(), $pengerjaan->id);

        return response()->json([
            'message' => 'OK',
            'data' => new HasilLatihanResource($selesai),
        ]);
    }
}
