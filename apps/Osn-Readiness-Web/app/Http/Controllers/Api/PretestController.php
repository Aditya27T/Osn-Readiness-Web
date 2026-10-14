<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\PretestServiceInterface;
use App\DTOs\HasilPretest;
use App\DTOs\PretestDimulai;
use App\Http\Requests\Pretest\MulaiRequest;
use App\Http\Requests\Pretest\SimpanJawabanRequest;
use App\Http\Requests\Pretest\SubmitRequest;
use App\Http\Resources\HasilPretestResource;
use App\Http\Resources\PretestResource;
use App\Models\Pretest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PretestController
{
    public function __construct(
        private readonly PretestServiceInterface $pretestService,
    ) {}

    public function store(MulaiRequest $request): JsonResponse
    {
        $dimulai = $this->pretestService->mulai($request->user(), $request->integer('tingkat_id'));

        // Pre-test baru dibalas 201. Pre-test yang sudah berjalan dikembalikan
        // ulang dengan 200 supaya siswa tidak kehilangan jawaban yang sudah ia isi.
        return response()->json([
            'message' => 'OK',
            'data' => new PretestResource($dimulai),
        ], $dimulai->baru ? 201 : 200);
    }

    public function show(Request $request, Pretest $pretest): JsonResponse
    {
        $hasil = $this->pretestService->hasil($request->user(), $pretest->id);

        // Sudah dinilai: balas hasil, bukan daftar soal.
        if ($hasil instanceof HasilPretest) {
            return $this->balasanHasil($hasil);
        }

        return $this->balasan($this->pretestService->ringkasan($request->user(), $pretest->id));
    }

    public function simpanJawaban(SimpanJawabanRequest $request, Pretest $pretest): JsonResponse
    {
        $this->pretestService->simpanJawaban(
            $request->user(),
            $pretest->id,
            $request->integer('soal_id'),
            $request->input('jawaban_user'),
        );

        return response()->json(['message' => 'Jawaban tersimpan']);
    }

    public function submit(SubmitRequest $request, Pretest $pretest): JsonResponse
    {
        return $this->balasanHasil($this->pretestService->submit($request->user(), $pretest->id));
    }

    private function balasan(PretestDimulai $dimulai): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => new PretestResource($dimulai),
        ]);
    }

    private function balasanHasil(HasilPretest $hasil): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => new HasilPretestResource($hasil),
        ]);
    }
}
