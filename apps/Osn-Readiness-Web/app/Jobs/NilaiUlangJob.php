<?php

namespace App\Jobs;

use App\Contracts\Services\LatihanServiceInterface;
use App\Contracts\Services\PenilaianServiceInterface;
use App\Contracts\Services\PretestServiceInterface;
use App\Contracts\Services\SimulasiServiceInterface;
use App\Enums\JenisPengerjaan;
use App\Exceptions\PerhitunganKonfigurasiException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mengulang penilaian yang gagal.
 *
 * Diterima ketika Python tidak tersedia saat submit, sehingga jawaban sudah
 * terkunci tetapi belum bernilai. Method selesaikanPenilaian di service yang
 * sesuai bersifat idempoten, jadi job ini aman dijalankan berulang kali.
 */
class NilaiUlangJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Lima percobaan dengan jeda yang makin panjang. Jeda sepanjang ini
     * sudah tidak ada di dalam request HTTP.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60, 120, 300];

    public int $tries = 5;

    public function __construct(
        public readonly JenisPengerjaan $jenis,
        public readonly int $id,
    ) {}

    public function handle(
        PretestServiceInterface $pretest,
        LatihanServiceInterface $latihan,
        SimulasiServiceInterface $simulasi,
    ): void {
        try {
            $this->service($pretest, $latihan, $simulasi)->selesaikanPenilaian($this->id);
        } catch (PerhitunganKonfigurasiException $exception) {
            // Kesalahan konfigurasi tidak akan membaik dengan mencoba lagi,
            // jadi job langsung gagal tanpa menghabiskan sisa percobaan.
            $this->fail($exception);
        }
    }

    /**
     * Dipanggil saat job berhenti untuk selamanya: percobaan habis, atau
     * digagalkan langsung karena kesalahan konfigurasi. Job masuk failed_jobs
     * dan tercatat untuk admin.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Penilaian gagal dan tidak akan dicoba lagi.', [
            'jenis' => $this->jenis->value,
            'id' => $this->id,
            'exception' => $exception === null ? null : $exception::class,
            'pesan' => $exception?->getMessage(),
        ]);
    }

    private function service(
        PretestServiceInterface $pretest,
        LatihanServiceInterface $latihan,
        SimulasiServiceInterface $simulasi,
    ): PenilaianServiceInterface {
        return match ($this->jenis) {
            JenisPengerjaan::Pretest => $pretest,
            JenisPengerjaan::Latihan => $latihan,
            JenisPengerjaan::Simulasi => $simulasi,
        };
    }
}
