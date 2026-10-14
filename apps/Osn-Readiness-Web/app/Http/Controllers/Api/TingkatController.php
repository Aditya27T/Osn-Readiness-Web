<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\TingkatServiceInterface;
use App\Http\Resources\TingkatResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TingkatController
{
    public function __construct(
        private readonly TingkatServiceInterface $tingkatService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => TingkatResource::collection($this->tingkatService->daftar($request->user())),
        ]);
    }
}
