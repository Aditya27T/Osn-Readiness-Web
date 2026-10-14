<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Resource yang punya CRUD admin, dengan abilities bokapnya.
     *
     * @var array<string, array<int, string>>
     */
    private const RESOURCE_ABILITAS = [
        'siswa' => ['viewAny', 'view', 'create', 'update', 'delete', 'deactivate'],
        'tingkat' => ['viewAny', 'view', 'update'],
        'kompetensi' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'materi' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'konteks-soal' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'soal' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'pembahasan' => ['viewAny', 'view', 'create', 'update'],
        'simulasi' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'quiz' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'aturan-pemetaan' => ['viewAny', 'view', 'update'],
        'bank-soal' => ['viewAny'],
        'dashboard-admin' => ['viewAny'],
    ];

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [];

        foreach (self::RESOURCE_ABILITAS as $resource => $abilities) {
            foreach ($abilities as $ability) {
                $permissions[] = Permission::firstOrCreate([
                    'name' => "{$resource}.{$ability}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

        // Super Admin memakai bypass Policy::before(), jadi tetap dapat semua permission
        $superAdmin->syncPermissions($permissions);
    }
}
