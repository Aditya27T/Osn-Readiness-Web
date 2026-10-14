<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\DashboardServiceInterface;
use Illuminate\Http\Request;

class DashboardController
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboardService,
    ) {}

    public function show(Request $request)
    {
        return response()->json([
            'message' => 'OK',
            'data' => $this->dashboardService->untuk($request->user()),
        ]);
    }
}
