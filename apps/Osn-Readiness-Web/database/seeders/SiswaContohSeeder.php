<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun siswa contoh untuk pengembangan dan test E2E.
 *
 * Akun ini bukan pilihan gaya: `e2e/helpers.js` meng-hardcode-nya sebagai
 * AKUN_SISWA, dan `auth.spec.js` bergantung pada akun ini SUDAH ADA sebelum
 * test "register menolak email yang sudah dipakai" dijalankan. Test lain
 * membuat akunnya sendiri lewat `buatSiswaBaru()`, tapi dua hal ini tidak.
 *
 * Karena itu akun harus dibuat lewat seed — bukan manual lewat tinker —
 * supaya tetap ada setiap kali `migrate:fresh --seed` dijalankan.
 */
class SiswaContohSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SISWA_CONTOH_EMAIL', 'siswa@example.com');
        $password = env('SISWA_CONTOH_PASSWORD', 'password');

        // withTrashed() + restore supaya seeder tetap idempotent kalau akun ini
        // pernah dinonaktifkan (soft delete) oleh proses lain.
        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        $user->fill([
            'name' => 'Siswa Contoh',
            // Model User memakai cast 'hashed', jadi jangan Hash::make() di sini.
            'password' => $password,
            'is_active' => true,
        ]);

        if ($user->trashed()) {
            $user->restore();
        }

        $user->save();
        $user->assignRole('siswa');
    }
}
