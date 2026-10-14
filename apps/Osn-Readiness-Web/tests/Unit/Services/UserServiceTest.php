<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_register_user_creates_user_and_assigns_role(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);

        $user = new User;
        $user->name = 'Test User';
        $user->email = 'test@example.com';

        $mockUser = Mockery::mock($user)->makePartial();
        $mockUser->shouldReceive('assignRole')
            ->once()
            ->with('siswa')
            ->andReturnSelf();

        $userRepository->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
            ])
            ->andReturn($mockUser);

        $userService = new UserService($userRepository);

        $result = $userService->registerUser([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertEquals('Test User', $result->name);
        $this->assertEquals('test@example.com', $result->email);
    }

    public function test_update_user_calls_repository(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);

        $user = User::factory()->create();

        $userRepository->shouldReceive('update')
            ->once()
            ->with($user, ['name' => 'Updated Name'])
            ->andReturn($user->fill(['name' => 'Updated Name']));

        $userService = new UserService($userRepository);

        $result = $userService->updateUser($user, ['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $result->name);
    }

    public function test_delete_user_calls_repository(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);

        $user = User::factory()->create();

        $userRepository->shouldReceive('delete')
            ->once()
            ->with($user)
            ->andReturn(true);

        $userService = new UserService($userRepository);

        $result = $userService->deleteUser($user);

        $this->assertTrue($result);
    }

    public function test_deactivate_user_menonaktifkan_dan_mencabut_semua_token(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);

        $user = User::factory()->create();
        $user->createToken('a')->plainTextToken;
        $user->createToken('b')->plainTextToken;
        $this->assertSame(2, $user->tokens()->count());

        $userRepository->shouldReceive('update')
            ->once()
            ->with($user, ['is_active' => false])
            ->andReturn($user->forceFill(['is_active' => false]));

        $result = (new UserService($userRepository))->deactivateUser($user);

        $this->assertFalse($result->is_active);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_delete_user_mencabut_semua_token(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);

        $user = User::factory()->create();
        $user->createToken('a')->plainTextToken;
        $this->assertSame(1, $user->tokens()->count());

        $userRepository->shouldReceive('delete')
            ->once()
            ->with($user)
            ->andReturn(true);

        $result = (new UserService($userRepository))->deleteUser($user);

        $this->assertTrue($result);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_update_user_menonaktifkan_akan_mencabut_token(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);

        $user = User::factory()->create();
        $user->createToken('a')->plainTextToken;

        $userRepository->shouldReceive('update')
            ->once()
            ->andReturn($user->forceFill(['is_active' => false]));

        (new UserService($userRepository))->updateUser($user, ['is_active' => false]);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_list_calls_repository_paginate(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);

        $paginator = new LengthAwarePaginator(
            collect(),
            0,
            15,
        );

        $userRepository->shouldReceive('paginate')
            ->once()
            ->with(15)
            ->andReturn($paginator);

        $userService = new UserService($userRepository);

        $result = $userService->list(15);

        $this->assertEquals(0, $result->total());
    }
}
