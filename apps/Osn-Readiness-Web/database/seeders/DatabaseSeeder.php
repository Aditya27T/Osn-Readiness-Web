<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            TingkatSeleksiSeeder::class,
            AturanPemetaanSeeder::class,
            SuperAdminSeeder::class,
        ]);

        // Konten dan akun contoh hanya untuk pengembangan lokal dan test.
        if (app()->environment('local', 'testing')) {
            // WAJIB di dalam blok ini. Akun siswa contoh berpassword default
            // dan jadi milik e2e/helpers.js sebagai AKUN_SISWA; kalau ikut
            // tercipta di production setiap kali `migrate --seed` dijalankan,
            // ada akun dengan password yang diketahui publik.
            $this->call(SiswaContohSeeder::class);

            // Bank soal kabupaten nyata menggantikan KontenContohSeeder yang
            // memakai SoalFactory. Tanpa KONTEN_KABUPATEN_FOLDER, seeder ini
            // hanya mencetak peringatan dan tidak apa-apa.
            $this->call(KontenKabupatenSeeder::class);
        }
    }
}
