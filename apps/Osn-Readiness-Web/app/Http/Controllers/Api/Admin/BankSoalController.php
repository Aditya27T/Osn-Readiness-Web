<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\BankSoalServiceInterface;
use App\Http\Requests\TingkatWajibRequest;
use Illuminate\Http\JsonResponse;

class BankSoalController
{
    public function __construct(
        private readonly BankSoalServiceInterface $bankSoalService,
    ) {}

    public function kecukupan(TingkatWajibRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => $this->bankSoalService->laporan($request->integer('tingkat_id')),
        ]);
    }
}
