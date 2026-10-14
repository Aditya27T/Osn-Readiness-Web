<?php

use App\Exceptions\BisnisException;
use App\Http\Middleware\CheckUserStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => CheckUserStatus::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Kesalahan bisnis adalah jawaban yang sah untuk klien (401, 403, 409),
        // bukan kerusakan, jadi tidak dicatat sebagai ERROR beserta stack
        // trace. Yang berstatus 5xx tetap dicatat dengan rinciannya supaya
        // admin tahu apa yang harus dilengkapi, misalnya bank soal yang kurang.
        $exceptions->report(function (BisnisException $e): bool {
            if ($e->getStatus() >= 500) {
                Log::warning($e->getMessage(), [
                    'kode' => $e->getKode(),
                    'detail' => json_decode($e->getDetail(), true) ?? $e->getDetail(),
                ]);
            }

            return false;
        });

        $exceptions->render(function (BisnisException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'kode' => $e->getKode(),
                'detail' => $e->getDetail(),
            ], $e->getStatus());
        });
    })->create();
