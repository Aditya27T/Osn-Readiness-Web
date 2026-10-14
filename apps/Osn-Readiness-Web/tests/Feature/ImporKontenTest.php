<?php

namespace Tests\Feature;

use App\Models\KonteksSoal;
use App\Models\Materi;
use App\Models\Pembahasan;
use App\Models\Pretest;
use App\Models\PretestJawaban;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImporKontenTest extends TestCase
{
    use RefreshDatabase;

    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Storage::fake('public');

        $this->folder = dirname(__DIR__).'/Fixtures/impor';
    }

    private function bankTingkat(): TingkatSeleksi
    {
        return TingkatSeleksi::factory()->create(['urutan' => 1]);
    }

    public function test_dry_run_tidak_menyisakan_data(): void
    {
        $this->bankTingkat();

        $this->artisan('impor:konten', ['folder' => $this->folder, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('materi', 0);
        $this->assertDatabaseCount('soal', 0);
        $this->assertDatabaseCount('kompetensi', 0);
        $this->assertDatabaseCount('konteks_soal', 0);

        // Gambar juga tidak disalin pada dry-run.
        Storage::disk('public')->assertMissing('materi/kab-01-gambar-1.png');
    }

    public function test_impor_materi_soal_konteks_pembahasan_dan_gambar(): void
    {
        $this->bankTingkat();

        $this->artisan('impor:konten', ['folder' => $this->folder])
            ->assertSuccessful();

        $materi = Materi::query()->where('id_sumber', 'kab-01')->firstOrFail();

        $this->assertSame('Aljabar Boolean & Teori Himpunan', $materi->judul);
        $this->assertStringContainsString('/storage/materi/kab-01-gambar-1.png', $materi->isi_materi);
        $this->assertStringNotContainsString('../gambar/', $materi->isi_materi);

        // Gambar materi tersalin ke storage publik.
        Storage::disk('public')->assertExists('materi/kab-01-gambar-1.png');
        Storage::disk('public')->assertExists('soal/kab-2020-001.png');

        // Kompetensi dibuat otomatis bila namanya belum ada.
        $this->assertDatabaseHas('kompetensi', [
            'tingkat_id' => $materi->tingkat_id,
            'nama_kompetensi' => 'Aljabar Boolean',
        ]);

        // Dua soal masuk: satu ditolak (kunci E bukan pilihan).
        $this->assertSame(2, Soal::count());

        $pilihan = Soal::query()->where('id_sumber', 'kab-2020-001')->firstOrFail();
        $this->assertSame('pilihan_ganda', $pilihan->tipe_soal->value);
        $this->assertSame('B', $pilihan->kunci_jawaban);
        $this->assertSame('soal/kab-2020-001.png', $pilihan->gambar);

        $isian = Soal::query()->where('id_sumber', 'kab-2020-002')->firstOrFail();
        $this->assertSame('isian', $isian->tipe_soal->value);
        $this->assertNull($isian->pilihan_jawaban);

        // Hanya soal pertama yang punya cerita; cerita sama dipakai satu baris.
        $this->assertSame(1, KonteksSoal::count());
        $this->assertNotNull($pilihan->konteks_id);
        $this->assertNull($isian->konteks_id);

        // Pembahasan hanya untuk soal yang punya.
        $this->assertSame(1, Pembahasan::count());
        $this->assertSame($pilihan->id, Pembahasan::firstOrFail()->soal_id);
    }

    public function test_impor_ulang_melalui_upsert_id_sumber(): void
    {
        $this->bankTingkat();

        $this->artisan('impor:konten', ['folder' => $this->folder])->assertSuccessful();
        $this->artisan('impor:konten', ['folder' => $this->folder])->assertSuccessful();

        // Tidak ada duplikasi: upsert mengganti, bukan menambah.
        $this->assertSame(1, Materi::count());
        $this->assertSame(2, Soal::count());
    }

    public function test_materi_bertumburu_dengan_materi_lain_tetap_impor(): void
    {
        // Tingkat ini sudah punya materi urutan 1 dari seed contoh; berkas
        // impor juga meminta urutan 1, sehingga importer harus memilih slot
        // kosong, bukan gagal pada unique constraint.
        $tingkat = $this->bankTingkat();
        Materi::factory()->create([
            'tingkat_id' => $tingkat->id,
            'urutan' => 1,
            'id_sumber' => 'sudah-ada',
        ]);

        $this->artisan('impor:konten', ['folder' => $this->folder])->assertSuccessful();

        $materi = Materi::query()->where('id_sumber', 'kab-01')->firstOrFail();

        $this->assertNotSame(1, $materi->urutan);
        $this->assertDatabaseHas('materi', ['id_sumber' => 'sudah-ada', 'urutan' => 1]);
    }

    public function test_impor_ulang_tidak_menabrak_urutan_materi_lain(): void
    {
        $tingkat = $this->bankTingkat();

        // Materi lain memakai urutan 1 lebih dulu, sehingga berkas impor yang
        // juga meminta urutan 1 harus tetap bisa diperbarui.
        Materi::factory()->create([
            'tingkat_id' => $tingkat->id,
            'urutan' => 1,
            'id_sumber' => 'sudah-ada',
        ]);

        $this->artisan('impor:konten', ['folder' => $this->folder])->assertSuccessful();
        $kedua = $this->artisan('impor:konten', ['folder' => $this->folder]);

        $kedua->assertSuccessful();
        $this->assertDatabaseHas('materi', ['id_sumber' => 'sudah-ada', 'urutan' => 1]);
    }

    public function test_alt_teks_gambar_materi_tetap_utuh(): void
    {
        $this->bankTingkat();

        $this->artisan('impor:konten', ['folder' => $this->folder])->assertSuccessful();

        $materi = Materi::query()->where('id_sumber', 'kab-01')->firstOrFail();

        // Alt teks harus utuh; string dobel-kutip akan membaca $1 sebagai
        // variabel PHP dan mengosongkan teksnya.
        $this->assertStringContainsString('![Diagram Venn](/storage/materi/kab-01-gambar-1.png)', $materi->isi_materi);
    }

    public function test_id_sumber_kembar_membatalkan_seluruh_impor(): void
    {
        $this->bankTingkat();

        // Berkas kedua memuat id_sumber yang sama dengan berkas pertama.
        file_put_contents(
            $this->folder.'/soal_provinsi.json',
            json_encode([
                [
                    'id_sumber' => 'kab-2020-001',
                    'tingkat' => 'kabupaten',
                    'materi' => 'kab-01',
                    'tingkat_kesulitan' => 'mudah',
                    'peruntukan' => 'pretest',
                    'deskripsi_soal' => null,
                    'soal' => 'Duplikat.',
                    'pilihan' => [],
                    'jawaban_benar' => 'apa saja',
                    'pembahasan' => null,
                    'gambar' => null,
                ],
            ])
        );

        $this->artisan('impor:konten', ['folder' => $this->folder])
            ->assertFailed();

        $this->assertDatabaseCount('materi', 0);
        $this->assertDatabaseCount('soal', 0);

        unlink($this->folder.'/soal_provinsi.json');
    }

    public function test_perubahan_terlarang_pada_soal_terpakai_dilewati(): void
    {
        // Pakai salinan folder di temp lain supaya fixture asli tidak ikut
        // berubah bila test gagal di tengah.
        $folder = sys_get_temp_dir().'/impor-'.uniqid();

        exec('cp -R '.escapeshellarg($this->folder).' '.escapeshellarg($folder));

        try {
            $this->bankTingkat();

            $this->artisan('impor:konten', ['folder' => $folder])->assertSuccessful();

            $soal = Soal::query()->where('id_sumber', 'kab-2020-001')->firstOrFail();

            // Soal dipakai pre-test: kunci dan materinya terkunci.
            $pretest = Pretest::factory()->create([
                'user_id' => User::factory()->create()->id,
                'tingkat_id' => $soal->tingkat_id,
            ]);

            PretestJawaban::create([
                'pretest_id' => $pretest->id,
                'soal_id' => $soal->id,
                'urutan' => 1,
                'bobot' => 1,
            ]);

            // Sumber kini mengubah kunci B menjadi D dan menperbaiki teks soal.
            $berkas = $folder.'/soal_kabupaten.json';
            $isi = json_decode((string) file_get_contents($berkas), true);
            $isi[0]['jawaban_benar'] = 'D';
            $isi[0]['soal'] = 'Teks soal diperbaiki.';
            file_put_contents($berkas, json_encode($isi, JSON_PRETTY_PRINT));

            $this->artisan('impor:konten', ['folder' => $folder])
                ->assertSuccessful();

            // Kunci tetap, teks soal boleh diperbaiki.
            $soal->refresh();
            $this->assertSame('B', $soal->kunci_jawaban);
            $this->assertSame('Teks soal diperbaiki.', $soal->pertanyaan);
        } finally {
            exec('rm -rf '.escapeshellarg($folder));
        }
    }

    /**
     * Jalankan impor atas salinan fixture yang soal pertamanya sudah diubah.
     *
     * @param  array<string, mixed>  $ubah
     */
    private function imporDenganSoalPertama(array $ubah, bool $harusSukses = true): void
    {
        $folder = sys_get_temp_dir().'/impor-'.uniqid();

        exec('cp -R '.escapeshellarg($this->folder).' '.escapeshellarg($folder));

        try {
            $berkas = $folder.'/soal_kabupaten.json';
            $isi = json_decode((string) file_get_contents($berkas), true);
            $isi[0] = array_merge($isi[0], $ubah);
            file_put_contents($berkas, json_encode($isi, JSON_PRETTY_PRINT));

            // run() dipanggil eksplisit: perintah harus selesai sebelum
            // folder sementara dihapus di blok finally.
            $perintah = $this->artisan('impor:konten', ['folder' => $folder]);

            if ($harusSukses) {
                $perintah->assertSuccessful()->run();
            } else {
                $perintah->assertFailed()->run();
            }
        } finally {
            exec('rm -rf '.escapeshellarg($folder));
        }
    }

    public function test_level_atau_peruntukan_tidak_sah_hanya_menolak_soal_itu(): void
    {
        $this->bankTingkat();

        $this->imporDenganSoalPertama(['tingkat_kesulitan' => 'gampang']);

        // Soal pertama ditolak; materi dan soal lain tetap masuk.
        $this->assertDatabaseMissing('soal', ['id_sumber' => 'kab-2020-001']);
        $this->assertDatabaseHas('soal', ['id_sumber' => 'kab-2020-002']);
        $this->assertDatabaseHas('materi', ['id_sumber' => 'kab-01']);
    }

    public function test_peruntukan_tidak_sah_hanya_menolak_soal_itu(): void
    {
        $this->bankTingkat();

        $this->imporDenganSoalPertama(['peruntukan' => 'ujian']);

        $this->assertDatabaseMissing('soal', ['id_sumber' => 'kab-2020-001']);
        $this->assertDatabaseHas('soal', ['id_sumber' => 'kab-2020-002']);
    }

    public function test_materi_dari_tingkat_lain_menolak_soal(): void
    {
        $this->bankTingkat();
        TingkatSeleksi::factory()->create(['urutan' => 2]);

        // Soal mengaku tingkat provinsi tetapi materinya kab-01 (kabupaten).
        $this->imporDenganSoalPertama(['tingkat' => 'provinsi']);

        $this->assertDatabaseMissing('soal', ['id_sumber' => 'kab-2020-001']);
        $this->assertDatabaseHas('soal', ['id_sumber' => 'kab-2020-002']);
    }

    public function test_soal_isian_memakai_tingkat_kesulitan_dari_berkas(): void
    {
        $this->bankTingkat();

        $this->imporDenganSoalPertama([
            'pilihan' => [],
            'jawaban_benar' => '2',
            'tingkat_kesulitan' => 'sulit',
        ]);

        $this->assertDatabaseHas('soal', [
            'id_sumber' => 'kab-2020-001',
            'tipe_soal' => 'isian',
            'level' => 'sulit',
        ]);
    }

    public function test_id_sumber_materi_kembar_membatalkan_seluruh_impor(): void
    {
        $this->bankTingkat();

        $folder = sys_get_temp_dir().'/impor-'.uniqid();

        exec('cp -R '.escapeshellarg($this->folder).' '.escapeshellarg($folder));

        try {
            copy($folder.'/materi/kab-01.md', $folder.'/materi/kab-01-salinan.md');

            $this->artisan('impor:konten', ['folder' => $folder])->assertFailed();

            $this->assertDatabaseCount('materi', 0);
            $this->assertDatabaseCount('soal', 0);
        } finally {
            exec('rm -rf '.escapeshellarg($folder));
        }
    }
}
