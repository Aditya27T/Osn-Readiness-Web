<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\UserServiceInterface;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UserController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $perPage = (int) request('per_page', 15);

        return UserResource::collection($this->userService->list($perPage));
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return response()->json([
            'message' => 'OK',
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $user = $this->userService->registerUser($request->validated());

        return response()->json([
            'message' => 'User berhasil ditambahkan',
            'data' => new UserResource($user->load('roles')),
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $user = $this->userService->updateUser($user, $request->validated());

        return response()->json([
            'message' => 'User berhasil diperbarui',
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('delete', $user);

        $this->userService->deleteUser($user);

        return response()->json([
            'message' => 'User berhasil dihapus',
        ]);
    }
}
