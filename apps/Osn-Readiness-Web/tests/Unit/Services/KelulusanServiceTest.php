<?php

namespace Tests\Unit\Services;

use App\Models\HasilSimulasi;
use App\Models\KenaikanTingkat;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\ProgressBelajar;
use App\Models\Quiz;
use App\Models\QuizJawaban;
use App\Models\QuizPengerjaan;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use App\Services\KelulusanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelulusanServiceTest extends TestCase
{
    use RefreshDatabase;

    private KelulusanService $layanan;

    private TingkatSeleksi $tingkat;

    private TingkatSeleksi $provinsi;

    private Pretest $putaran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->layanan = $this->app->make(KelulusanService::class);

        $this->tingkat = TingkatSeleksi::factory()->create(['urutan' => 1]);
        $this->provinsi = TingkatSeleksi::factory()->create(['urutan' => 2]);

        $user = User::factory()->create();

        $this->putaran = Pretest::factory()->create([
            'user_id' => $user->id,
            'tingkat_id' => $this->tingkat->id,
        ]);

        $this->userId = $user->id;
    }

    private int $userId;

    private function hasil(float $nilai, ?Simulasi $simulasi = null): HasilSimulasi
    {
        $simulasi ??= Simulasi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'is_aktif' => true,
        ]);

        return HasilSimulasi::factory()->create([
            'user_id' => $this->userId,
            'simulasi_id' => $simulasi->id,
            'pretest_id' => $this->putaran->id,
            'nilai' => $nilai,
            'jumlah_benar' => 5,
            'jumlah_salah' => 5,
            'selesai_pada' => now(),
        ]);
    }

    public function test_lulus_menulis_kenaikan_ke_tingkat_berikutnya(): void
    {
        $balasan = $this->layanan->menilai($this->hasil(85.0));

        $this->assertTrue($balasan['lulus']);
        $this->assertSame($this->provinsi->id, $balasan['tingkat_tujuan_id']);

        $this->assertDatabaseHas('kenaikan_tingkat', [
            'user_id' => $this->userId,
            'tingkat_asal_id' => $this->tingkat->id,
            'tingkat_tujuan_id' => $this->provinsi->id,
            'status' => 'lulus',
        ]);

        $this->assertDatabaseHas('hasil_simulasi', [
            'id' => $this->hasil(85.0)->id,
        ]);
    }

    public function test_lulus_provinsi_tujuan_kosong(): void
    {
        $this->putaran->update(['tingkat_id' => $this->provinsi->id]);

        $simulasiProvinsi = Simulasi::factory()->create([
            'tingkat_id' => $this->provinsi->id,
            'is_aktif' => true,
        ]);

        $balasan = $this->layanan->menilai($this->hasil(90.0, $simulasiProvinsi));

        $this->assertTrue($balasan['lulus']);
        $this->assertNull($balasan['tingkat_tujuan_id']);

        $this->assertDatabaseHas('kenaikan_tingkat', [
            'user_id' => $this->userId,
            'status' => 'lulus',
            'tingkat_tujuan_id' => null,
        ]);
    }

    public function test_belum_maksimal_hanya_menandai_tidak_lulus(): void
    {
        // Passing grade 70: 60 gagal, percobaan pertama dari tiga.
        $hasil = $this->hasil(60.0);

        $balasan = $this->layanan->menilai($hasil);

        $this->assertFalse($balasan['lulus']);
        $this->assertNull($balasan['tingkat_tujuan_id']);

        $this->assertDatabaseHas('hasil_simulasi', [
            'id' => $hasil->id,
            'lulus' => false,
        ]);

        $this->assertDatabaseMissing('kenaikan_tingkat', [
            'user_id' => $this->userId,
        ]);
    }

    public function test_gagal_ketiga_menghapus_data_dan_menulis_tidak_lulus(): void
    {
        $this->buatDataPutaran();

        // Tiga kegagalan 60 < 70; simulasi_maks_percobaan bawaan 3.
        $this->layanan->menilai($this->hasil(60.0));
        $this->layanan->menilai($this->hasil(60.0));
        $this->layanan->menilai($this->hasil(60.0));

        // Progress belajar dan pengerjaan latihan tingkat ini terhapus,
        // jawabannya ikut terhapus lewat cascade.
        $this->assertDatabaseMissing('progress_belajar', ['user_id' => $this->userId]);
        $this->assertDatabaseMissing('quiz_pengerjaan', ['user_id' => $this->userId]);
        $this->assertDatabaseMissing('quiz_jawaban', []);

        // Pre-test, hasil simulasi, dan rekomendasinya TIDAK dihapus.
        $this->assertDatabaseHas('pretest', ['id' => $this->putaran->id]);
        $this->assertSame(3, HasilSimulasi::count());

        $this->assertDatabaseHas('kenaikan_tingkat', [
            'user_id' => $this->userId,
            'tingkat_asal_id' => $this->tingkat->id,
            'status' => 'tidak_lulus',
        ]);
    }

    public function test_nilai_terbaik_dan_passing_grade_masuk_keterangan(): void
    {
        $this->layanan->menilai($this->hasil(50.0));
        $this->layanan->menilai($this->hasil(65.0));
        $this->layanan->menilai($this->hasil(40.0));

        $keterangan = KenaikanTingkat::query()
            ->where('user_id', $this->userId)
            ->where('status', 'tidak_lulus')
            ->value('keterangan');

        $this->assertStringContainsString('65', (string) $keterangan);
        $this->assertStringContainsString('70', (string) $keterangan);
    }

    private function buatDataPutaran(): void
    {
        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $this->tingkat->id]);

        $materi = Materi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'kompetensi_id' => $kompetensi->id,
        ]);

        ProgressBelajar::create([
            'user_id' => $this->userId,
            'materi_id' => $materi->id,
            'status' => 'selesai',
            'persentase' => 100,
            'tanggal_selesai' => now(),
        ]);

        $quiz = Quiz::create([
            'materi_id' => $materi->id,
            'nama_quiz' => 'Quiz',
            'jumlah_soal' => 10,
        ]);

        $pengerjaan = QuizPengerjaan::create([
            'user_id' => $this->userId,
            'quiz_id' => $quiz->id,
            'nilai' => 80.0,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);

        QuizJawaban::create([
            'pengerjaan_id' => $pengerjaan->id,
            'soal_id' => Soal::factory()->create([
                'tingkat_id' => $this->tingkat->id,
                'materi_id' => $materi->id,
                'peruntukan' => 'latihan',
            ])->id,
            'urutan' => 1,
            'bobot' => 1,
        ]);
    }

    public function test_menilai_lulus_dua_kali_tidak_menggandakan_dan_tidak_membatalkan_transaksi(): void
    {
        $hasil = $this->hasil(90.0);

        $this->layanan->menilai($hasil);
        $this->layanan->menilai($hasil);

        // Bila insert kembar sampai melempar unique violation, transaksi
        // Postgres batal dan query berikut ikut gagal.
        $this->assertSame(1, KenaikanTingkat::query()->where('status', 'lulus')->count());
    }
}
