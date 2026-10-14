<?php

namespace App\Console\Commands;

use App\Contracts\Services\ImporKontenServiceInterface;
use Illuminate\Console\Command;

class ImporKontenCommand extends Command
{
    protected $signature = 'impor:konten
        {folder : Path folder impor berisi materi/, soal_*.json, dan gambar/}
        {--dry-run : Hitung dan laporkan tanpa menyimpan apa pun}
        {--tingkat= : Batasi impor ke satu tingkat (kabupaten atau provinsi)}';

    protected $description = 'Impor materi Markdown, soal JSON, dan gambar ke database via id_sumber';

    public function handle(ImporKontenServiceInterface $imporKontenService): int
    {
        $folder = rtrim($this->argument('folder'), '/');
        $tingkat = $this->option('tingkat');

        try {
            $laporan = $imporKontenService->impor(
                $folder,
                (bool) $this->option('dry-run'),
                is_string($tingkat) && $tingkat !== '' ? $tingkat : null,
            );
        } catch (\RuntimeException $kegagalan) {
            $this->error($kegagalan->getMessage());

            return self::FAILURE;
        }

        if ($laporan['dry_run']) {
            $this->info('DRY RUN — tidak ada yang disimpan.');
        }

        $this->info(sprintf(
            'Materi: %d masuk, %d diperbarui.',
            $laporan['materi']['masuk'],
            $laporan['materi']['diperbarui'],
        ));

        $this->info(sprintf(
            'Soal: %d masuk, %d diperbarui, %d ditolak, %d dilewati.',
            $laporan['soal']['masuk'],
            $laporan['soal']['diperbarui'],
            count($laporan['soal']['ditolak']),
            count($laporan['soal']['dilewati']),
        ));

        foreach ($laporan['soal']['ditolak'] as $baris) {
            $this->warn("DITOLAK {$baris['id_sumber']}: {$baris['alasan']}");
        }

        foreach ($laporan['soal']['dilewati'] as $baris) {
            $this->warn("DILEWATI {$baris['id_sumber']}: {$baris['alasan']}");
        }

        return self::SUCCESS;
    }
}
