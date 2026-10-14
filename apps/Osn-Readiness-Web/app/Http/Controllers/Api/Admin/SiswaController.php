<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Services\SiswaServiceInterface;
use App\Http\Requests\Admin\Siswa\UpdateSiswaRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SiswaController
{
    public function __construct(
        private readonly SiswaServiceInterface $siswaService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $perPage = (int) request('per_page', 15);

        return UserResource::collection($this->siswaService->getAll($perPage));
    }

    public function show(User $siswa): JsonResponse
    {
        Gate::authorize('view', $siswa);

        return response()->json([
            'message' => 'OK',
            'data' => new UserResource($this->siswaService->getById($siswa->id)->load('roles')),
        ]);
    }

    public function update(UpdateSiswaRequest $request, User $siswa): JsonResponse
    {
        Gate::authorize('update', $siswa);

        $updated = $this->siswaService->update($siswa->id, $request->validated());

        return response()->json([
            'message' => 'Siswa berhasil diperbarui',
            'data' => new UserResource($updated->load('roles')),
        ]);
    }

    public function deactivate(User $siswa): JsonResponse
    {
        Gate::authorize('deactivate', $siswa);

        $updated = $this->siswaService->deactivate($siswa->id);

        return response()->json([
            'message' => 'Siswa berhasil dinonaktifkan',
            'data' => new UserResource($updated->load('roles')),
        ]);
    }

    public function destroy(User $siswa): JsonResponse
    {
        Gate::authorize('delete', $siswa);

        $this->siswaService->delete($siswa->id);

        return response()->json([
            'message' => 'Siswa berhasil dihapus',
        ]);
    }
}
