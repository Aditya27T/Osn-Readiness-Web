<?php

namespace App\Clients;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Exceptions\PerhitunganKonfigurasiException;
use App\Exceptions\PerhitunganTidakTersediaException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Klien layanan hitung Python.
 *
 * Retry hanya untuk koneksi gagal, timeout, dan 5xx, dengan jeda pendek karena
 * pemanggilan ini berjalan di dalam siklus web. Jeda panjang untuk recovering
 * dari kegagalan yang berulang adalah milik NilaiUlangJob, bukan milik klien.
 */
class PerhitunganClient implements PerhitunganClientInterface
{
    /**
     * Jeda antar percobaan dalam milidetik. Cukup pendek supaya tidak menahan
     * request web.
     */
    private const jedaRetryMs = 200;

    public function __construct(
        private readonly string $url,
        private readonly string $token,
        private readonly int $timeout = 5,
        private readonly int $retry = 2,
    ) {}

    public function hitungPenilaian(array $soal): array
    {
        $endpoint = '/hitung/penilaian';
        $balasan = $this->kirim($endpoint, ['soal' => $soal]);

        return [
            'nilai' => $this->angka($balasan, 'nilai', $endpoint),
            'jawaban' => $this->daftarJawaban($balasan, array_column($soal, 'soal_id'), $endpoint),
        ];
    }

    public function hitungPretest(array $soal, array $materi, int $jumlahMateriWajib): array
    {
        $endpoint = '/hitung/pretest';

        $balasan = $this->kirim($endpoint, [
            'soal' => $soal,
            'materi' => $materi,
            'jumlah_materi_wajib' => $jumlahMateriWajib,
        ]);

        $pemetaan = $this->daftarPemetaan($balasan, $endpoint);
        $materiWajib = $this->daftarMateriWajib($balasan, $endpoint);

        // Setiap materi yang dikirim harus punya baris pemetaan.
        foreach (array_column($materi, 'materi_id') as $materiId) {
            if (! in_array($materiId, array_column($pemetaan, 'materi_id'), true)) {
                throw $this->tidakCocok($endpoint, $balasan, "Materi {$materiId} tidak punya baris pemetaan.");
            }
        }

        if (count($materiWajib) !== $jumlahMateriWajib) {
            throw $this->tidakCocok(
                $endpoint,
                $balasan,
                'Balasan berisi '.count($materiWajib)." materi wajib, aturan bilang {$jumlahMateriWajib}."
            );
        }

        return [
            'nilai' => $this->angka($balasan, 'nilai', $endpoint),
            'jawaban' => $this->daftarJawaban($balasan, array_column($soal, 'soal_id'), $endpoint),
            'pemetaan' => $pemetaan,
            'materi_wajib' => $materiWajib,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function kirim(string $endpoint, array $payload): array
    {
        $response = $this->panggil($endpoint, $payload);

        if (! $response instanceof Response) {
            throw new PerhitunganTidakTersediaException(
                "Tidak ada balasan dari {$endpoint}; koneksi gagal atau melewati timeout {$this->timeout} detik."
            );
        }

        $status = $response->status();

        // 5xx setelah retry masih dianggap gagal sementara: layanan sedang
        // sibuk, bukan salah konfigurasi.
        if ($status >= 500) {
            throw new PerhitunganTidakTersediaException(
                "Layanan hitung membalas {$status} dari {$endpoint} setelah {$this->retry} kali percobaan ulang."
            );
        }

        // 403 dan 422 menandakan token atau bentuk permintaan yang salah, jadi
        // dicatat sebagai error kritis dan tidak dicoba lagi.
        if ($response->failed()) {
            Log::critical('Layanan hitung menolak permintaan.', [
                'endpoint' => $endpoint,
                'status' => $status,
                'body' => self::potong($response->body()),
            ]);

            throw new PerhitunganKonfigurasiException(
                "Layanan hitung membalas {$status} dari {$endpoint}: ".self::potong($response->body())
            );
        }

        $balasan = $response->json();

        if (! is_array($balasan)) {
            throw $this->tidakCocok($endpoint, [], 'Balasan bukan objek JSON.');
        }

        return $balasan;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function panggil(string $endpoint, array $payload): ?Response
    {
        try {
            return Http::withHeaders([
                'X-Internal-Token' => $this->token,
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry(
                    // Http::retry menghitung percobaan total, sedangkan
                    // PERHITUNGAN_RETRY berarti jumlah percobaan ulang.
                    $this->retry + 1,
                    self::jedaRetryMs,
                    // Retry hanya untuk koneksi, timeout, dan 5xx.
                    fn (Throwable $exception): bool => $this->bisaDicobaLagi($exception),
                    throw: false,
                )
                ->post($this->url.$endpoint, $payload);
        } catch (Throwable $exception) {
            if ($this->bisaDicobaLagi($exception)) {
                return null;
            }

            throw $this->tidakCocok($endpoint, [], $exception->getMessage());
        }
    }

    private function bisaDicobaLagi(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && $exception->response !== null
            && $exception->response->status() >= 500;
    }

    /**
     * Setiap soal yang dikirim harus dijawab persis satu kali.
     *
     * @param  array<string, mixed>  $balasan
     * @param  array<int, int>  $soalTerkirim
     * @return array<int, array{soal_id: int, status_benar: bool}>
     */
    private function daftarJawaban(array $balasan, array $soalTerkirim, string $endpoint): array
    {
        if (! isset($balasan['jawaban']) || ! is_array($balasan['jawaban'])) {
            throw $this->tidakCocok($endpoint, $balasan, 'Field jawaban tidak ada atau bukan array.');
        }

        $daftar = [];

        foreach ($balasan['jawaban'] as $baris) {
            if (! is_array($baris) || ! isset($baris['soal_id']) || ! array_key_exists('status_benar', $baris)) {
                throw $this->tidakCocok($endpoint, $balasan, 'Baris jawaban tidak punya soal_id dan status_benar.');
            }

            if (! is_bool($baris['status_benar'])) {
                throw $this->tidakCocok($endpoint, $balasan, 'status_benar harus boolean.');
            }

            $daftar[] = [
                'soal_id' => (int) $baris['soal_id'],
                'status_benar' => $baris['status_benar'],
            ];
        }

        if (count($daftar) !== count($soalTerkirim)) {
            throw $this->tidakCocok(
                $endpoint,
                $balasan,
                'Balasan berisi '.count($daftar).' jawaban, yang dikirim '.count($soalTerkirim).'.'
            );
        }

        foreach ($soalTerkirim as $soalId) {
            if (! in_array((int) $soalId, array_column($daftar, 'soal_id'), true)) {
                throw $this->tidakCocok($endpoint, $balasan, "Soal {$soalId} tidak dijawab di balasan.");
            }
        }

        return $daftar;
    }

    /**
     * @param  array<string, mixed>  $balasan
     * @return array<int, array{materi_id: int, jumlah_soal: int, jumlah_benar: int, poin_didapat: int, poin_maksimal: int, persentase: float, peringkat: int}>
     */
    private function daftarPemetaan(array $balasan, string $endpoint): array
    {
        if (! isset($balasan['pemetaan']) || ! is_array($balasan['pemetaan'])) {
            throw $this->tidakCocok($endpoint, $balasan, 'Field pemetaan tidak ada atau bukan array.');
        }

        $daftar = [];

        foreach ($balasan['pemetaan'] as $baris) {
            foreach (['materi_id', 'jumlah_soal', 'jumlah_benar', 'poin_didapat', 'poin_maksimal', 'peringkat'] as $wajib) {
                if (! is_array($baris) || ! isset($baris[$wajib])) {
                    throw $this->tidakCocok($endpoint, $balasan, "Baris pemetaan tidak punya {$wajib}.");
                }
            }

            $daftar[] = [
                'materi_id' => (int) $baris['materi_id'],
                'jumlah_soal' => (int) $baris['jumlah_soal'],
                'jumlah_benar' => (int) $baris['jumlah_benar'],
                'poin_didapat' => (int) $baris['poin_didapat'],
                'poin_maksimal' => (int) $baris['poin_maksimal'],
                'persentase' => (float) ($baris['persentase'] ?? 0),
                'peringkat' => (int) $baris['peringkat'],
            ];
        }

        return $daftar;
    }

    /**
     * @param  array<string, mixed>  $balasan
     * @return array<int, array{materi_id: int, prioritas: int}>
     */
    private function daftarMateriWajib(array $balasan, string $endpoint): array
    {
        if (! isset($balasan['materi_wajib']) || ! is_array($balasan['materi_wajib'])) {
            throw $this->tidakCocok($endpoint, $balasan, 'Field materi_wajib tidak ada atau bukan array.');
        }

        $daftar = [];

        foreach ($balasan['materi_wajib'] as $baris) {
            if (! is_array($baris) || ! isset($baris['materi_id'], $baris['prioritas'])) {
                throw $this->tidakCocok($endpoint, $balasan, 'Baris materi_wajib tidak punya materi_id dan prioritas.');
            }

            $daftar[] = [
                'materi_id' => (int) $baris['materi_id'],
                'prioritas' => (int) $baris['prioritas'],
            ];
        }

        return $daftar;
    }

    /**
     * @param  array<string, mixed>  $balasan
     */
    private function angka(array $balasan, string $field, string $endpoint): float
    {
        if (! isset($balasan[$field]) || ! is_numeric($balasan[$field])) {
            throw $this->tidakCocok($endpoint, $balasan, "Field {$field} tidak ada atau bukan angka.");
        }

        return (float) $balasan[$field];
    }

    /**
     * @param  array<string, mixed>  $balasan
     */
    private function tidakCocok(string $endpoint, array $balasan, string $alasan): PerhitunganKonfigurasiException
    {
        Log::critical('Balasan layanan hitung tidak cocok dengan permintaan.', [
            'endpoint' => $endpoint,
            'alasan' => $alasan,
            'balasan' => $balasan,
        ]);

        return new PerhitunganKonfigurasiException($alasan);
    }

    private static function potong(string $teks, int $maks = 200): string
    {
        return mb_strlen($teks) > $maks ? mb_substr($teks, 0, $maks).'...' : $teks;
    }
}
