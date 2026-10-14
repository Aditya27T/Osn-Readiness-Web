<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\BelajarServiceInterface;
use App\Http\Requests\Belajar\ProgressRequest;
use App\Http\Resources\MateriBelajarResource;
use App\Models\Materi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class BelajarController
{
    public function __construct(
        private readonly BelajarServiceInterface $belajarService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $materi = $this->belajarService->daftar($request->user(), $this->tingkatId($request));

        return MateriBelajarResource::collection($materi);
    }

    public function show(Request $request, Materi $materi): MateriBelajarResource
    {
        return new MateriBelajarResource(
            $this->belajarService->detail($request->user(), $materi)
        );
    }

    public function perbaruiProgress(ProgressRequest $request, Materi $materi): JsonResponse
    {
        $progress = $this->belajarService->perbaruiProgress(
            $request->user(),
            (int) $materi->id,
            $request->string('status')->toString(),
        );

        return response()->json([
            'message' => 'Progress tersimpan',
            'data' => [
                'materi_id' => (int) $materi->id,
                'status' => $progress->status->value,
                'persentase' => (int) $progress->persentase,
                'tanggal_selesai' => $progress->tanggal_selesai?->toISOString(),
            ],
        ]);
    }

    private function tingkatId(Request $request): int
    {
        /** @var User $user */
        $user = $request->user();

        $tingkatId = $request->query('tingkat_id') ?? $user->tingkat_aktif_id;

        if ($tingkatId === null || ! is_numeric($tingkatId)) {
            throw ValidationException::withMessages([
                'tingkat_id' => ['Tingkat belum diketahui: kirim tingkat_id atau selesaikan pre-test dulu.'],
            ]);
        }

        return (int) $tingkatId;
    }
}
