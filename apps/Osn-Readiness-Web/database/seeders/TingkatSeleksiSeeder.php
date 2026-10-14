<?php

namespace Database\Seeders;

use App\Models\TingkatSeleksi;
use Illuminate\Database\Seeder;

class TingkatSeleksiSeeder extends Seeder
{
    public function run(): void
    {
        // Observer aturan dimatikan saat seeding supaya aturan bawaan observer
        // tidak mengalahkan nilai spesifik dari AturanPemetaanSeeder.
        TingkatSeleksi::withoutEvents(fn () => $this->buat());
    }

    private function buat(): void
    {
        TingkatSeleksi::firstOrCreate(
            ['urutan' => 1],
            [
                'nama_tingkat' => 'Kabupaten/Kota',
                'deskripsi' => 'Tahap seleksi OSN tingkat kabupaten/kota.',
            ],
        );

        TingkatSeleksi::firstOrCreate(
            ['urutan' => 2],
            [
                'nama_tingkat' => 'Provinsi',
                'deskripsi' => 'Tahap seleksi OSN tingkat provinsi.',
            ],
        );
    }
}
