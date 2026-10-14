<?php

namespace App\Console\Commands;

use App\Contracts\Services\SimulasiServiceInterface;
use Illuminate\Console\Command;

class TutupSimulasiKedaluwarsa extends Command
{
    protected $signature = 'simulasi:tutup-kedaluwarsa';

    protected $description = 'Nilai percobaan simulasi yang batas waktunya sudah lewat tetapi belum disubmit';

    public function handle(SimulasiServiceInterface $simulasiService): int
    {
        $ditutup = $simulasiService->tutupKedaluwarsa();

        $this->info("{$ditutup} percobaan simulasi ditutup.");

        return self::SUCCESS;
    }
}
