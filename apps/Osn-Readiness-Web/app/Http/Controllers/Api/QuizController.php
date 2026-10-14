<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\LatihanServiceInterface;
use App\Http\Resources\LatihanPengerjaanResource;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController
{
    public function __construct(
        private readonly LatihanServiceInterface $latihanService,
    ) {}

    public function mulai(Request $request, Quiz $quiz): JsonResponse
    {
        $dimulai = $this->latihanService->mulai($request->user(), $quiz);

        // Latihan baru dibalas 201. Pengerjaan yang belum selesai
        // dikembalikan ulang dengan 200 supaya siswa menyambung, bukan
        // mengulang dari nol.
        return response()->json([
            'message' => 'OK',
            'data' => new LatihanPengerjaanResource($dimulai),
        ], $dimulai->baru ? 201 : 200);
    }
}
