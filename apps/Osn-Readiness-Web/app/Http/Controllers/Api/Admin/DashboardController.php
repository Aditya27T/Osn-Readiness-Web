<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\DashboardAdminServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function __construct(
        private readonly DashboardAdminServiceInterface $dashboardService,
    ) {}

    public function ringkasan(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => $this->dashboardService->ringkasan(),
        ]);
    }
}
