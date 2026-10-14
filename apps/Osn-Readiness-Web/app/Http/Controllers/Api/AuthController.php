<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\AuthServiceInterface;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Requests\Auth\ApiRegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    public function register(ApiRegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => new UserResource($result['user']->load('roles')),
                'token' => $result['token'],
            ],
        ], 201);
    }

    public function login(ApiLoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'message' => 'Login berhasil',
            'data' => [
                'user' => new UserResource($result['user']->load('roles')),
                'token' => $result['token'],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => new UserResource($request->user()->load('roles')),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => new UserResource($user->load('roles')),
        ]);
    }
}
