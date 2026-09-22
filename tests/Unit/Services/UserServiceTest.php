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
            ->with('user')
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
