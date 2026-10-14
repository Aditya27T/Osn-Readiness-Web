<?php

namespace Tests\Unit\Jobs;

use App\Contracts\Services\LatihanServiceInterface;
use App\Contracts\Services\PenilaianServiceInterface;
use App\Contracts\Services\PretestServiceInterface;
use App\Contracts\Services\SimulasiServiceInterface;
use App\Enums\JenisPengerjaan;
use App\Exceptions\PerhitunganKonfigurasiException;
use App\Exceptions\PerhitunganTidakTersediaException;
use App\Jobs\NilaiUlangJob;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NilaiUlangJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function lima_percobaan_dengan_jeda_makin_panjang(): void
    {
        $job = new NilaiUlangJob(JenisPengerjaan::Pretest, 1);

        $this->assertSame(5, $job->tries);
        $this->assertSame([10, 30, 60, 120, 300], $job->backoff);
    }

    #[Test]
    public function jeda_panjang_tidak_lagi_ada_di_client(): void
    {
        $isi = (string) file_get_contents(app_path('Clients/PerhitunganClient.php'));

        // Jeda 10 sampai 300 detik tidak boleh ada di dalam request HTTP.
        $this->assertStringNotContainsString('sleep(', $isi);
        $this->assertStringNotContainsString('retryBackoff', $isi);
    }

    /**
     * @return array<string, array{JenisPengerjaan, string}>
     */
    public static function jenisPengerjaan(): array
    {
        return [
            'pretest' => [JenisPengerjaan::Pretest, 'pretest'],
            'latihan' => [JenisPengerjaan::Latihan, 'latihan'],
            'simulasi' => [JenisPengerjaan::Simulasi, 'simulasi'],
        ];
    }

    #[Test]
    #[DataProvider('jenisPengerjaan')]
    public function job_menyerahkan_id_kepada_service_yang_sesuai(JenisPengerjaan $jenis, string $harus): void
    {
        $pretest = Mockery::mock(PretestServiceInterface::class);
        $latihan = Mockery::mock(LatihanServiceInterface::class);
        $simulasi = Mockery::mock(SimulasiServiceInterface::class);

        $pakai = [$pretest, $latihan, $simulasi];

        foreach ($pakai as $service) {
            if ($service === $pakai[$this->indeks($jenis)]) {
                $service->shouldReceive('selesaikanPenilaian')->once()->with(77);
            } else {
                $service->shouldReceive('selesaikanPenilaian')->never();
            }
        }

        (new NilaiUlangJob($jenis, 77))->handle($pretest, $latihan, $simulasi);

        $this->assertSame($harus, $jenis->value);
    }

    private function indeks(JenisPengerjaan $jenis): int
    {
        return match ($jenis) {
            JenisPengerjaan::Pretest => 0,
            JenisPengerjaan::Latihan => 1,
            JenisPengerjaan::Simulasi => 2,
        };
    }

    #[Test]
    public function gagal_selalu_mencatat_log_untuk_admin(): void
    {
        $tercatat = false;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $pesan, array $konteks) use (&$tercatat): bool {
                $tercatat = $pesan === 'Penilaian gagal dan tidak akan dicoba lagi.'
                    && $konteks['jenis'] === 'simulasi'
                    && $konteks['id'] === 9
                    && $konteks['pesan'] === 'python mati';

                return $tercatat;
            });

        (new NilaiUlangJob(JenisPengerjaan::Simulasi, 9))->failed(new \RuntimeException('python mati'));

        static::assertTrue($tercatat);
    }

    #[Test]
    public function ketiga_service_mewarisi_penilaian_service(): void
    {
        $this->assertTrue(is_subclass_of(PretestServiceInterface::class, PenilaianServiceInterface::class));
        $this->assertTrue(is_subclass_of(LatihanServiceInterface::class, PenilaianServiceInterface::class));
        $this->assertTrue(is_subclass_of(SimulasiServiceInterface::class, PenilaianServiceInterface::class));
    }

    #[Test]
    public function job_menyimpan_jenis_dan_id_sebagai_properti_publik(): void
    {
        $job = new NilaiUlangJob(JenisPengerjaan::Latihan, 42);

        $this->assertSame(JenisPengerjaan::Latihan, $job->jenis);
        $this->assertSame(42, $job->id);
    }

    #[Test]
    public function kesalahan_konfigurasi_langsung_menggagalkan_job_tanpa_retry(): void
    {
        $galat = new PerhitunganKonfigurasiException('Token ditolak.');

        $pretest = Mockery::mock(PretestServiceInterface::class);
        $pretest->shouldReceive('selesaikanPenilaian')->once()->with(5)->andThrow($galat);

        $job = (new NilaiUlangJob(JenisPengerjaan::Pretest, 5))->withFakeQueueInteractions();

        // Tidak dilempar ulang: exception yang lolos dari handle() membuat
        // queue menjadwalkan percobaan berikutnya.
        $job->handle(
            $pretest,
            Mockery::mock(LatihanServiceInterface::class),
            Mockery::mock(SimulasiServiceInterface::class),
        );

        $job->assertFailedWith($galat);
    }

    #[Test]
    public function kegagalan_sementara_dilempar_ulang_supaya_queue_mencoba_lagi(): void
    {
        $pretest = Mockery::mock(PretestServiceInterface::class);
        $pretest->shouldReceive('selesaikanPenilaian')->once()
            ->andThrow(new PerhitunganTidakTersediaException('Timeout.'));

        $job = (new NilaiUlangJob(JenisPengerjaan::Pretest, 5))->withFakeQueueInteractions();

        try {
            $job->handle(
                $pretest,
                Mockery::mock(LatihanServiceInterface::class),
                Mockery::mock(SimulasiServiceInterface::class),
            );

            $this->fail('Kegagalan sementara harus dilempar ulang.');
        } catch (PerhitunganTidakTersediaException) {
            $job->assertNotFailed();
        }
    }
}
