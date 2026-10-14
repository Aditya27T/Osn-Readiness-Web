<?php

namespace Tests\Unit\Services;

use App\Contracts\Randomizers\RandomizerInterface;
use App\Contracts\Repositories\MateriRepositoryInterface;
use App\Contracts\Repositories\SoalRepositoryInterface;
use App\Contracts\Services\AturanServiceInterface;
use App\DTOs\AturanTingkat;
use App\DTOs\PermintaanSoal;
use App\DTOs\SoalTerpilih;
use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Exceptions\BankSoalTidakCukupException;
use App\Models\Materi;
use App\Models\Soal;
use App\Randomizers\AcakRandomizer;
use App\Randomizers\SeededRandomizer;
use App\Services\SoalPickerService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit test algoritma pengambilan soal.
 *
 * Repository di-mock dan kandidat dibuat sebagai model in-memory, jadi test
 * ini tidak menyentuh database sama sekali. Seed randomizer tetap supaya
 * urutan acak bisa diulang persis.
 */
class SoalPickerServiceTest extends TestCase
{
    private const PERSEN_PRETEST = ['mudah' => 50.0, 'sedang' => 30.0, 'sulit' => 20.0];

    private const PERSEN_SIMULASI = ['mudah' => 30.0, 'sedang' => 40.0, 'sulit' => 30.0];

    /** @var array<int, Materi> */
    private array $materi = [];

    /** @var array<int, Soal> */
    private array $bank = [];

    #[Test]
    public function container_memakai_randomizer_produksi_tanpa_seed_tetap(): void
    {
        $this->assertInstanceOf(AcakRandomizer::class, $this->app->make(RandomizerInterface::class));
    }

    #[Test]
    public function latihan_memakai_campuran_level_saat_soal_mudah_tidak_cukup(): void
    {
        $this->siapkanMateri(2);
        $this->siapkanBank(['mudah' => 2, 'sedang' => 4, 'sulit' => 4], Peruntukan::Latihan);

        $hasil = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Latihan,
            jumlahSoal: 10,
            materiId: 1,
        ));

        $this->assertCount(10, array_unique($hasil->soalIds()));
        $this->assertSame(['mudah' => 2, 'sedang' => 4, 'sulit' => 4], $hasil->jumlahPerLevel());
        $this->assertSame([1 => 10], $this->hitungPerMateri($hasil));
    }

    #[Test]
    public function mode_bebas_memenuhi_batas_materi_dari_semua_level(): void
    {
        $this->siapkanMateri(2);
        $this->siapkanBank(['mudah' => 0, 'sedang' => 1, 'sulit' => 1], Peruntukan::Latihan);

        $hasil = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Latihan,
            jumlahSoal: 4,
            minimalSoalPerMateri: 2,
        ));

        $this->assertCount(4, array_unique($hasil->soalIds()));
        $this->assertSame([1 => 2, 2 => 2], $this->hitungPerMateri($hasil));
    }

    #[Test]
    public function simulasi_melengkapi_lima_soal_baru_dengan_tiga_cadangan_unik(): void
    {
        $this->siapkanMateri(1);
        $this->siapkanBank(['mudah' => 10, 'sedang' => 0, 'sulit' => 0], Peruntukan::Simulasi);

        $hasil = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Simulasi,
            jumlahSoal: 8,
            persenLevel: ['mudah' => 100.0],
            soalDihindari: range(6, 10),
        ));

        $this->assertCount(8, array_unique($hasil->soalIds()));
        $this->assertEqualsCanonicalizing(range(1, 5), array_intersect(range(1, 5), $hasil->soalIds()));
        $this->assertCount(3, array_intersect(range(6, 10), $hasil->soalIds()));
    }

    #[Test]
    public function simulasi_menolak_bank_yang_tetap_kurang_setelah_cadangan(): void
    {
        $this->siapkanMateri(1);
        $this->siapkanBank(['mudah' => 7, 'sedang' => 0, 'sulit' => 0], Peruntukan::Simulasi);

        try {
            $this->picker()->pilih(new PermintaanSoal(
                tingkatId: 1,
                peruntukan: Peruntukan::Simulasi,
                jumlahSoal: 8,
                persenLevel: ['mudah' => 100.0],
                soalDihindari: [6, 7],
            ));

            $this->fail('Bank yang kurang harus ditolak meskipun memiliki cadangan.');
        } catch (BankSoalTidakCukupException $e) {
            $this->assertSame(['mudah' => 1], json_decode($e->getDetail(), true)['kurang_per_level']);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function pretest_kabupaten_membagi_sepuluh_batas_materi_dan_duapuluh_sisa(): void
    {
        $this->siapkanMateri(5);
        $this->siapkanBank(['mudah' => 40, 'sedang' => 30, 'sulit' => 20]);

        $hasil = $this->picker()->pilih($this->permintaanPretest(tingkatId: 1, minimal: 2));

        $this->assertSame(30, $hasil->jumlah());
        $this->assertSame(
            ['mudah' => 15, 'sedang' => 9, 'sulit' => 6],
            $hasil->jumlahPerLevel(),
        );

        // Langkah 3 guaranteeing 2 per materi, langkah 4 mengisi sisa kuota
        // tanpa batas materi, sehingga satu materi bisa dapat lebih dari 2.
        $perMateri = $this->hitungPerMateri($hasil);
        $this->assertCount(5, $perMateri);

        foreach ($perMateri as $materiId => $jumlah) {
            $this->assertGreaterThanOrEqual(2, $jumlah, "Materi {$materiId} kurang dari batas 2.");
        }

        // 5 materi x 2 = 10 soal untuk langkah 3, sisanya 20 soal.
        $this->assertSame(30, array_sum($perMateri));
        $this->assertGreaterThanOrEqual(10, $this->jumlahLevelMudah($hasil));
    }

    #[Test]
    public function pretest_provinsi_membagi_duapuluh_batas_materi_dan_sepuluh_sisa(): void
    {
        $this->siapkanMateri(10);
        $this->siapkanBank(['mudah' => 40, 'sedang' => 30, 'sulit' => 20]);

        $hasil = $this->picker()->pilih($this->permintaanPretest(tingkatId: 2, minimal: 2));

        $this->assertSame(30, $hasil->jumlah());
        $this->assertSame(
            ['mudah' => 15, 'sedang' => 9, 'sulit' => 6],
            $hasil->jumlahPerLevel(),
        );

        // 10 materi x 2 = 20 soal batas materi, sisanya 10 soal.
        $perMateri = $this->hitungPerMateri($hasil);
        $this->assertCount(10, $perMateri);

        foreach ($perMateri as $materiId => $jumlah) {
            $this->assertGreaterThanOrEqual(2, $jumlah, "Materi {$materiId} kurang dari batas 2.");
        }

        $this->assertSame(30, array_sum($perMateri));
    }

    #[Test]
    public function batas_materi_mengambil_dari_level_dengan_sisa_kuota_terbesar(): void
    {
        $this->siapkanMateri(5);
        $this->siapkanBank(['mudah' => 40, 'sedang' => 30, 'sulit' => 20]);

        // 10 soal untuk 5 materi x 2, sehingga seluruh paket habis oleh
        // langkah 3 dan langkah 4 tidak menambah apa pun.
        $hasil = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Pretest,
            jumlahSoal: 10,
            persenLevel: self::PERSEN_PRETEST,
            minimalSoalPerMateri: 2,
        ));

        $this->assertSame(10, $hasil->jumlah());

        // Tiap materi tepat dua soal.
        $perMateri = $this->hitungPerMateri($hasil);
        $this->assertSame([2, 2, 2, 2, 2], array_values($perMateri));

        // Urutan materi diacak, jadi yang dibuktikan adalah aturan
        // "ambil dari level dengan sisa kuota terbanyak": kuota 10 soal
        // terbagi 5/3/2 persis seperti urutan level dari mudah ke sulit.
        $this->assertSame(
            ['mudah' => 5, 'sedang' => 3, 'sulit' => 2],
            $hasil->jumlahPerLevel(),
        );
    }

    #[Test]
    public function soal_dikecualikan_tidak_pernah_terpakai_ulang(): void
    {
        $this->siapkanMateri(5);
        $this->siapkanBank(['mudah' => 40, 'sedang' => 30, 'sulit' => 20]);

        $pertama = $this->picker()->pilih($this->permintaanPretest(tingkatId: 1, minimal: 2));

        $kedua = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Pretest,
            jumlahSoal: 30,
            persenLevel: self::PERSEN_PRETEST,
            minimalSoalPerMateri: 2,
            soalDikecualikan: $pertama->soalIds(),
        ));

        $this->assertSame(30, $kedua->jumlah());
        $this->assertEmpty(
            array_intersect($pertama->soalIds(), $kedua->soalIds()),
            'Putaran kedua memakai soal yang sudah dipakai.',
        );
    }

    #[Test]
    public function urutan_berurutan_dan_bobot_mengikuti_level(): void
    {
        $this->siapkanMateri(5);
        $this->siapkanBank(['mudah' => 40, 'sedang' => 30, 'sulit' => 20]);

        $hasil = $this->picker()->pilih($this->permintaanPretest(tingkatId: 1, minimal: 2));

        $this->assertSame(range(1, 30), array_column($hasil->butir, 'urutan'));

        $bobotPerLevel = [
            Level::Mudah->value => 1,
            Level::Sedang->value => 2,
            Level::Sulit->value => 3,
        ];

        foreach ($hasil->butir as $butir) {
            $this->assertSame($bobotPerLevel[$butir['level']->value], $butir['bobot']);
        }
    }

    #[Test]
    public function seed_sama_menghasilkan_paket_sama_dan_seed_berbeda_menghasilkan_paket_berbeda(): void
    {
        $this->siapkanMateri(5);
        $this->siapkanBank(['mudah' => 40, 'sedang' => 30, 'sulit' => 20]);

        $samaSatu = $this->picker(777)->pilih($this->permintaanPretest(tingkatId: 1, minimal: 2));
        $samaDua = $this->picker(777)->pilih($this->permintaanPretest(tingkatId: 1, minimal: 2));
        $beda = $this->picker(778)->pilih($this->permintaanPretest(tingkatId: 1, minimal: 2));

        $this->assertSame($samaSatu->soalIds(), $samaDua->soalIds());
        $this->assertNotSame($samaSatu->soalIds(), $beda->soalIds());
    }

    #[Test]
    public function simulasi_mengutamakan_soal_yang_belum_pernah_dipakai(): void
    {
        $this->siapkanMateri(5);
        $this->siapkanBank(['mudah' => 40, 'sedang' => 30, 'sulit' => 20], Peruntukan::Simulasi);

        $dihindari = array_slice(array_keys($this->bank), 0, 9);

        $hasil = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Simulasi,
            jumlahSoal: 30,
            persenLevel: self::PERSEN_SIMULASI,
            soalDihindari: $dihindari,
        ));

        $this->assertSame(30, $hasil->jumlah());
        $this->assertEmpty(
            array_intersect($dihindari, $hasil->soalIds()),
            'Soal yang dihindari dipakai padahal kandidat lain masih cukup.',
        );
    }

    #[Test]
    public function simulasi_memakai_soal_yang_dihindari_bila_kandidat_lain_habis(): void
    {
        $this->siapkanMateri(1);
        // Soal mudah dihindari menjadi satu-satunya kandidat level mudah,
        // jadi tidak ada pilihan lain dan soal itu harus dipakai.
        $this->siapkanBank(['mudah' => 1, 'sedang' => 2, 'sulit' => 2], Peruntukan::Simulasi);

        $dihindari = [array_key_first($this->bank)];

        $hasil = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Simulasi,
            jumlahSoal: 3,
            persenLevel: self::PERSEN_SIMULASI,
            soalDihindari: $dihindari,
        ));

        $this->assertSame(3, $hasil->jumlah());
        $this->assertContains($dihindari[0], $hasil->soalIds());
    }

    #[Test]
    public function simulasi_mengikuti_kuota_30_40_30(): void
    {
        $this->siapkanMateri(5);
        $this->siapkanBank(['mudah' => 40, 'sedang' => 40, 'sulit' => 40], Peruntukan::Simulasi);

        $hasil = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Simulasi,
            jumlahSoal: 30,
            persenLevel: self::PERSEN_SIMULASI,
        ));

        $this->assertSame(['mudah' => 9, 'sedang' => 12, 'sulit' => 9], $hasil->jumlahPerLevel());
    }

    #[Test]
    public function latihan_tidak_membagi_level_dan_batasi_satu_materi(): void
    {
        $this->siapkanMateri(5);
        $this->siapkanBank(['mudah' => 20, 'sedang' => 20, 'sulit' => 20], Peruntukan::Latihan);

        $materiPertama = $this->materi[0]->id;

        $hasil = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Latihan,
            jumlahSoal: 10,
            materiId: $materiPertama,
        ));

        $this->assertSame(10, $hasil->jumlah());

        foreach ($hasil->butir as $butir) {
            $this->assertSame(
                $materiPertama,
                $this->bank[$butir['soal_id']]->materi_id,
                'Latihan mengambil soal dari luar materi yang diminta.',
            );
        }
    }

    #[Test]
    public function bank_kurang_per_level_melempar_exception_beserta_rinciannya(): void
    {
        $this->siapkanMateri(1);
        // Level sulit hanya dua soal, padahal kuotanya enam.
        $this->siapkanBank(['mudah' => 30, 'sedang' => 30, 'sulit' => 2]);

        try {
            $this->picker()->pilih($this->permintaanPretest(tingkatId: 1, minimal: 2));

            $this->fail('Harusnya melempar BankSoalTidakCukupException.');
        } catch (BankSoalTidakCukupException $e) {
            $this->assertSame('BANK_SOAL_TIDAK_CUKUP', $e->getKode());
            $this->assertSame(503, $e->getStatus());

            $rincian = json_decode($e->getDetail(), true);

            $this->assertSame(1, $rincian['tingkat_id']);
            $this->assertSame('pretest', $rincian['peruntukan']);
            $this->assertSame(30, $rincian['jumlah_soal']);
            $this->assertSame(['sulit' => 4], $rincian['kurang_per_level']);
        }
    }

    #[Test]
    public function bank_kurang_per_materi_melempar_exception(): void
    {
        $this->siapkanMateri(3);
        // Materi ketiga tidak punya soal sama sekali, sedangkan batas materi
        // menuntut dua soal dari tiap materi.
        $this->siapkanBank(
            ['mudah' => 30, 'sedang' => 30, 'sulit' => 30],
            Peruntukan::Pretest,
            [$this->materi[2]->id],
        );

        $this->expectException(BankSoalTidakCukupException::class);

        $this->picker()->pilih($this->permintaanPretest(tingkatId: 1, minimal: 2));
    }

    #[Test]
    public function tidak_pernah_mengembalikan_paket_kurang_dari_jumlah_soal(): void
    {
        $this->siapkanMateri(5);
        // Hanya satu soal per level per materi: totalnya jauh di bawah kuota
        // 15/9/6, jadi paket tidak mungkin lengkap.
        $this->siapkanBank(['mudah' => 1, 'sedang' => 1, 'sulit' => 1]);

        $this->expectException(BankSoalTidakCukupException::class);

        $this->picker()->pilih($this->permintaanPretest(tingkatId: 1, minimal: 2));
    }

    #[Test]
    public function level_dengan_kuota_nol_tidak_pernah_dipakai(): void
    {
        $this->siapkanMateri(5);
        $this->siapkanBank(['mudah' => 40, 'sedang' => 30, 'sulit' => 40]);

        // Persen level dengan satu level bernilai nol.
        $hasil = $this->picker()->pilih(new PermintaanSoal(
            tingkatId: 1,
            peruntukan: Peruntukan::Simulasi,
            jumlahSoal: 10,
            persenLevel: ['mudah' => 100.0, 'sedang' => 0.0, 'sulit' => 0.0],
        ));

        $this->assertSame(10, $hasil->jumlah());
        $this->assertSame(['mudah' => 10], $hasil->jumlahPerLevel());
    }

    private function permintaanPretest(int $tingkatId, int $minimal): PermintaanSoal
    {
        return new PermintaanSoal(
            tingkatId: $tingkatId,
            peruntukan: Peruntukan::Pretest,
            jumlahSoal: 30,
            persenLevel: self::PERSEN_PRETEST,
            minimalSoalPerMateri: $minimal,
        );
    }

    private function siapkanMateri(int $jumlah): void
    {
        $this->materi = [];

        for ($i = 1; $i <= $jumlah; $i++) {
            $materi = new Materi(['urutan' => $i, 'judul' => "Materi {$i}"]);
            $materi->id = $i;
            $materi->tingkat_id = 1;
            $materi->exists = true;

            $this->materi[] = $materi;
        }
    }

    /**
     * @param  array<string, int>  $perLevel
     * @param  array<int, int>  $materiKosong  materi yang sengaja tidak punya soal
     */
    private function siapkanBank(
        array $perLevel,
        Peruntukan $peruntukan = Peruntukan::Pretest,
        array $materiKosong = [],
    ): void {
        $this->bank = [];
        $id = 0;

        foreach ($this->materi as $materi) {
            if (in_array($materi->id, $materiKosong, true)) {
                continue;
            }

            foreach (Level::cases() as $level) {

                for ($i = 0; $i < $perLevel[$level->value]; $i++) {
                    $id++;
                    $soal = new Soal([
                        'materi_id' => $materi->id,
                        'level' => $level,
                        'peruntukan' => $peruntukan,
                    ]);
                    $soal->id = $id;
                    $soal->tingkat_id = 1;
                    $soal->exists = true;

                    $this->bank[$id] = $soal;
                }
            }
        }
    }

    private function picker(int $seed = 4242): SoalPickerService
    {
        $bank = $this->bank;

        $soalRepository = Mockery::mock(SoalRepositoryInterface::class);
        $soalRepository->shouldReceive('kandidat')
            ->andReturnUsing(function (
                int $tingkatId,
                Peruntukan $peruntukan,
                array $kecuali = [],
                ?int $materiId = null,
            ) use ($bank): Collection {
                $hasil = [];

                foreach ($bank as $id => $soal) {
                    if (in_array($id, $kecuali, true)) {
                        continue;
                    }

                    if ($materiId !== null && (int) $soal->materi_id !== $materiId) {
                        continue;
                    }

                    $hasil[$id] = $soal;
                }

                return Collection::make(array_values($hasil));
            });

        $materiRepository = Mockery::mock(MateriRepositoryInterface::class);
        $materiRepository->shouldReceive('untukTingkat')
            ->andReturn(Collection::make($this->materi));

        $aturanService = Mockery::mock(AturanServiceInterface::class);
        $aturanService->shouldReceive('untukTingkat')
            ->andReturnUsing(fn (int $tingkatId): AturanTingkat => new AturanTingkat(
                tingkatId: $tingkatId,
                bobotMudah: 1,
                bobotSedang: 2,
                bobotSulit: 3,
                pretestJumlahSoal: 30,
                persenLevelPretest: self::PERSEN_PRETEST,
                pretestMinSoalPerMateri: 2,
                jumlahMateriWajib: 3,
                latihanMinSoal: 10,
                latihanMinNilai: 50.0,
                persenLevelSimulasi: self::PERSEN_SIMULASI,
                simulasiMaksPercobaan: 3,
                passingGrade: 70.0,
            ));

        return new SoalPickerService(
            $soalRepository,
            $materiRepository,
            $aturanService,
            new SeededRandomizer($seed),
        );
    }

    private function jumlahLevelMudah(SoalTerpilih $hasil): int
    {
        return $hasil->jumlahPerLevel()[Level::Mudah->value] ?? 0;
    }

    /**
     * @return array<int, int>
     */
    private function hitungPerMateri(SoalTerpilih $hasil): array
    {
        $jumlah = [];

        foreach ($hasil->butir as $butir) {
            $materiId = (int) $this->bank[$butir['soal_id']]->materi_id;
            $jumlah[$materiId] = ($jumlah[$materiId] ?? 0) + 1;
        }

        ksort($jumlah);

        return $jumlah;
    }
}
