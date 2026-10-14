<?php

namespace Tests\Feature\Clients;

use App\Clients\PerhitunganClient;
use App\Exceptions\PerhitunganKonfigurasiException;
use App\Exceptions\PerhitunganTidakTersediaException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test kontrak: payload yang dikirim client dibandingkan dengan contoh pada
 * dokumen alur, dan balasan yang tidak cocok ditolak sebagai kesalahan
 * konfigurasi.
 */
class PerhitunganClientTest extends TestCase
{
    private const URL = 'http://layanan-hitung.test';

    /**
     * @return list<array{soal_id: int, tipe_soal: string, bobot: int, jawaban_user: ?string, kunci_jawaban: string}>
     */
    private function soal(int $jumlah = 2): array
    {
        $soal = [];

        for ($i = 1; $i <= $jumlah; $i++) {
            $soal[] = [
                'soal_id' => $i,
                'tipe_soal' => 'pilihan_ganda',
                'bobot' => $i,
                'jawaban_user' => $i === 1 ? 'A' : 'B',
                'kunci_jawaban' => 'A',
            ];
        }

        return $soal;
    }

    /**
     * @return list<array{materi_id: int, urutan: int}>
     */
    private function materi(): array
    {
        return [['materi_id' => 10, 'urutan' => 1], ['materi_id' => 11, 'urutan' => 2]];
    }

    private function client(): PerhitunganClient
    {
        return new PerhitunganClient(url: self::URL, token: 'token-rahasia', timeout: 5, retry: 2);
    }

    #[Test]
    public function hitung_penilaian_mengirim_header_token_dan_payload_soal(): void
    {
        Http::fake([
            self::URL.'/hitung/penilaian' => Http::response([
                'nilai' => 82.5,
                'jawaban' => [
                    ['soal_id' => 1, 'status_benar' => true],
                    ['soal_id' => 2, 'status_benar' => false],
                ],
            ]),
        ]);

        $hasil = $this->client()->hitungPenilaian($this->soal());

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/hitung/penilaian')) {
                return false;
            }

            $this->assertSame('token-rahasia', $request->header('X-Internal-Token')[0]);
            $this->assertSame(['soal' => $this->soal()], $request->data());

            return true;
        });

        $this->assertSame(82.5, $hasil['nilai']);
        $this->assertSame(
            [['soal_id' => 1, 'status_benar' => true], ['soal_id' => 2, 'status_benar' => false]],
            $hasil['jawaban'],
        );
    }

    #[Test]
    public function hitung_pretest_mengirim_soal_materi_dan_jumlah_materi_wajib(): void
    {
        Http::fake([
            self::URL.'/hitung/pretest' => Http::response([
                'nilai' => 70,
                'jawaban' => [['soal_id' => 1, 'status_benar' => true]],
                'pemetaan' => [[
                    'materi_id' => 10, 'jumlah_soal' => 1, 'jumlah_benar' => 1,
                    'poin_didapat' => 1, 'poin_maksimal' => 1, 'persentase' => 100.0, 'peringkat' => 1,
                ]],
                'materi_wajib' => [['materi_id' => 10, 'prioritas' => 1]],
            ]),
        ]);

        $soal = [
            [
                'soal_id' => 1, 'materi_id' => 10, 'tipe_soal' => 'pilihan_ganda',
                'bobot' => 1, 'jawaban_user' => 'A', 'kunci_jawaban' => 'A',
            ],
        ];

        $hasil = $this->client()->hitungPretest($soal, [['materi_id' => 10, 'urutan' => 1]], 1);

        Http::assertSent(function (Request $request): bool {
            $this->assertSame([
                'soal' => [[
                    'soal_id' => 1, 'materi_id' => 10, 'tipe_soal' => 'pilihan_ganda',
                    'bobot' => 1, 'jawaban_user' => 'A', 'kunci_jawaban' => 'A',
                ]],
                'materi' => [['materi_id' => 10, 'urutan' => 1]],
                'jumlah_materi_wajib' => 1,
            ], $request->data());

            return true;
        });

        $this->assertSame(70.0, $hasil['nilai']);
        $this->assertCount(1, $hasil['pemetaan']);
        $this->assertSame([['materi_id' => 10, 'prioritas' => 1]], $hasil['materi_wajib']);
    }

    #[Test]
    public function lima_server_diterjemahkan_ke_503_hasil_sedang_diproses(): void
    {
        Http::fake([self::URL.'/*' => Http::response('rusak', 503)]);

        try {
            $this->client()->hitungPenilaian($this->soal());
            $this->fail('Harusnya melempar PerhitunganTidakTersediaException.');
        } catch (PerhitunganTidakTersediaException $e) {
            $this->assertSame('HASIL_SEDANG_DIPROSES', $e->getKode());
            $this->assertSame(503, $e->getStatus());
        }
    }

    #[Test]
    public function lima_server_diterjemahkan_ke_tidak_tersedia(): void
    {
        Http::fake([self::URL.'/*' => Http::response('rusak', 503)]);

        $this->expectException(PerhitunganTidakTersediaException::class);

        $this->client()->hitungPenilaian($this->soal());
    }

    #[Test]
    public function token_ditolak_diterjemahkan_ke_502_konfigurasi(): void
    {
        Http::fake([self::URL.'/*' => Http::response(['message' => 'forbidden'], 403)]);

        try {
            $this->client()->hitungPenilaian($this->soal());
            $this->fail('Harusnya melempar PerhitunganKonfigurasiException.');
        } catch (PerhitunganKonfigurasiException $e) {
            $this->assertSame('LAYANAN_HITUNG_SALAH_KONFIGURASI', $e->getKode());
            $this->assertSame(502, $e->getStatus());
        }
    }

    #[Test]
    public function empat_dua_puluh_diterjemahkan_ke_502_konfigurasi(): void
    {
        Http::fake([self::URL.'/*' => Http::response(['message' => 'payload salah'], 422)]);

        $this->expectException(PerhitunganKonfigurasiException::class);

        $this->client()->hitungPenilaian($this->soal());
    }

    #[Test]
    public function jumlah_jawaban_yang_tidak_sesuai_ditolak(): void
    {
        Http::fake([
            self::URL.'/*' => Http::response([
                'nilai' => 50,
                'jawaban' => [['soal_id' => 1, 'status_benar' => true]],
            ]),
        ]);

        $this->expectException(PerhitunganKonfigurasiException::class);

        // Dua soal dikirim, satu dijawab.
        $this->client()->hitungPenilaian($this->soal(2));
    }

    #[Test]
    public function soal_yang_tidak_dijawab_di_balasan_ditolak(): void
    {
        Http::fake([
            self::URL.'/*' => Http::response([
                'nilai' => 50,
                'jawaban' => [
                    ['soal_id' => 99, 'status_benar' => true],
                    ['soal_id' => 98, 'status_benar' => true],
                ],
            ]),
        ]);

        $this->expectException(PerhitunganKonfigurasiException::class);

        $this->client()->hitungPenilaian($this->soal(2));
    }

    #[Test]
    public function status_benar_yang_bukan_boolean_ditolak(): void
    {
        Http::fake([
            self::URL.'/*' => Http::response([
                'nilai' => 50,
                'jawaban' => [
                    ['soal_id' => 1, 'status_benar' => 'ya'],
                    ['soal_id' => 2, 'status_benar' => true],
                ],
            ]),
        ]);

        $this->expectException(PerhitunganKonfigurasiException::class);

        $this->client()->hitungPenilaian($this->soal(2));
    }

    #[Test]
    public function nilai_yang_bukan_angka_ditolak(): void
    {
        Http::fake([
            self::URL.'/*' => Http::response([
                'nilai' => 'baik',
                'jawaban' => [
                    ['soal_id' => 1, 'status_benar' => true],
                    ['soal_id' => 2, 'status_benar' => true],
                ],
            ]),
        ]);

        $this->expectException(PerhitunganKonfigurasiException::class);

        $this->client()->hitungPenilaian($this->soal(2));
    }

    #[Test]
    public function balasan_bukan_json_ditolak(): void
    {
        Http::fake([self::URL.'/*' => Http::response('<html>bukan json</html>', 200)]);

        $this->expectException(PerhitunganKonfigurasiException::class);

        $this->client()->hitungPenilaian($this->soal());
    }

    #[Test]
    public function materi_yang_tidak_punya_pemetaan_ditolak(): void
    {
        Http::fake([
            self::URL.'/*' => Http::response([
                'nilai' => 70,
                'jawaban' => [['soal_id' => 1, 'status_benar' => true]],
                'pemetaan' => [[
                    'materi_id' => 10, 'jumlah_soal' => 1, 'jumlah_benar' => 1,
                    'poin_didapat' => 1, 'poin_maksimal' => 1, 'persentase' => 100.0, 'peringkat' => 1,
                ]],
                'materi_wajib' => [['materi_id' => 10, 'prioritas' => 1]],
            ]),
        ]);

        $this->expectException(PerhitunganKonfigurasiException::class);

        // Materi 11 dikirim tapi tidak punya baris pemetaan.
        $this->client()->hitungPretest(
            [['soal_id' => 1, 'materi_id' => 10, 'tipe_soal' => 'isian', 'bobot' => 1, 'jawaban_user' => 'x', 'kunci_jawaban' => 'x']],
            $this->materi(),
            1,
        );
    }

    #[Test]
    public function jumlah_materi_wajib_yang_tidak_sesuai_aturan_ditolak(): void
    {
        Http::fake([
            self::URL.'/*' => Http::response([
                'nilai' => 70,
                'jawaban' => [['soal_id' => 1, 'status_benar' => true]],
                'pemetaan' => [
                    [
                        'materi_id' => 10, 'jumlah_soal' => 1, 'jumlah_benar' => 1,
                        'poin_didapat' => 1, 'poin_maksimal' => 1, 'persentase' => 100.0, 'peringkat' => 1,
                    ],
                ],
                // Aturan bilang 2, balasan cuma mengirim 1.
                'materi_wajib' => [['materi_id' => 10, 'prioritas' => 1]],
            ]),
        ]);

        $this->expectException(PerhitunganKonfigurasiException::class);

        $this->client()->hitungPretest(
            [['soal_id' => 1, 'materi_id' => 10, 'tipe_soal' => 'isian', 'bobot' => 1, 'jawaban_user' => 'x', 'kunci_jawaban' => 'x']],
            $this->materi(),
            2,
        );
    }

    #[Test]
    public function retry_hanya_untuk_lima_server(): void
    {
        Http::fake([self::URL.'/*' => Http::response('rusak', 500)]);

        try {
            $this->client()->hitungPenilaian($this->soal());
        } catch (PerhitunganTidakTersediaException) {
            // yang diperiksa adalah jumlah percobaan di bawah
        }

        // Satu percobaan awal ditambah dua kali retry.
        Http::assertSentCount(3);
    }

    #[Test]
    public function empat_lima_tidak_dicoba_lagi(): void
    {
        Http::fake([self::URL.'/*' => Http::response('dilarang', 403)]);

        try {
            $this->client()->hitungPenilaian($this->soal());
        } catch (PerhitunganKonfigurasiException) {
            // yang diperiksa adalah jumlah percobaan di bawah
        }

        Http::assertSentCount(1);
    }

    #[Test]
    public function koneksi_gagal_diterjemahkan_ke_503_setelah_retry(): void
    {
        $percobaan = 0;

        Http::fake(function () use (&$percobaan): never {
            $percobaan++;

            throw new ConnectionException('Connection timed out');
        });

        try {
            $this->client()->hitungPenilaian($this->soal());
            $this->fail('Harusnya melempar PerhitunganTidakTersediaException.');
        } catch (PerhitunganTidakTersediaException $e) {
            $this->assertSame(503, $e->getStatus());
        }

        // Satu percobaan awal ditambah dua retry.
        $this->assertSame(3, $percobaan);
    }
}
