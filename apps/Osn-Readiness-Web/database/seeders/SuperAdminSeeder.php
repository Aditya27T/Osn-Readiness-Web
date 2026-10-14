<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@example.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'password');

        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        $user->fill([
            'name' => 'Super Admin',
            // Model User memakai cast 'hashed', jadi jangan Hash::make() di sini.
            'password' => $password,
            'is_active' => true,
        ]);

        if ($user->trashed()) {
            $user->restore();
        }

        $user->save();
        $user->assignRole('Super Admin');
    }
}
