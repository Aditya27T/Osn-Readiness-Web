<?php

namespace Tests\Feature\Api;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\ProgressBelajar;
use App\Models\Quiz;
use App\Models\QuizPengerjaan;
use App\Models\RekomendasiMateri;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakePerhitunganClient;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private FakePerhitunganClient $perhitungan;

    private User $siswa;

    private TingkatSeleksi $tingkat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->perhitungan = new FakePerhitunganClient;
        $this->app->instance(PerhitunganClientInterface::class, $this->perhitungan);

        $this->siswa = User::factory()->create();
        $this->siswa->assignRole('siswa');

        $this->tingkat = TingkatSeleksi::factory()->create(['urutan' => 1]);

        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $this->tingkat->id]);
        Materi::factory()->create(['tingkat_id' => $this->tingkat->id, 'kompetensi_id' => $kompetensi->id]);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_dashboard_sebelum_pretest_memuat_tahap_belum_pretest(): void
    {
        $data = $this->actingAs($this->siswa)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->json('data');

        $this->assertNull($data['tingkat_aktif_id']);
        $this->assertNull($data['tingkat_aktif']);

        $kabupaten = collect($data['tingkat'])->firstWhere('tingkat_id', $this->tingkat->id);

        $this->assertTrue($kabupaten['tingkat_terbuka']);
        $this->assertSame('BELUM_PRETEST', $kabupaten['tahap']);
        $this->assertFalse($kabupaten['sudah_lulus']);
        $this->assertSame(3, $kabupaten['sisa_kuota_simulasi']);
        $this->assertFalse($kabupaten['syarat_simulasi']['terpenuhi']);
        $this->assertNull($kabupaten['hasil_simulasi_terakhir']);
    }

    public function test_dashboard_sama_dengan_status_endpoint(): void
    {
        // Angka dashboard harus sama persis dengan yang dipakai penjaga:
        // tahap dan syarat dibaca dari service yang sama.
        $this->buatPutaranDenganSyaratTerpenuhi();

        $dashboard = $this->actingAs($this->siswa)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->json('data');

        $syarat = $this->actingAs($this->siswa)
            ->getJson('/api/simulasi/syarat?tingkat_id='.$this->tingkat->id)
            ->assertOk()
            ->json('data');

        $kabupaten = collect($dashboard['tingkat'])->firstWhere('tingkat_id', $this->tingkat->id);

        $this->assertSame('SIAP_SIMULASI', $kabupaten['tahap']);
        $this->assertSame($syarat['terpenuhi'], $kabupaten['syarat_simulasi']['terpenuhi']);
        $this->assertSame($syarat['rincian'], $kabupaten['syarat_simulasi']['rincian']);
    }

    public function test_dashboard_setelah_lulus_menampilkan_status_lulus(): void
    {
        $this->buatPutaranDenganSyaratTerpenuhi();

        $this->perhitungan->nilai = 85.0;

        Simulasi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'is_aktif' => true,
        ]);

        $this->isiBankSimulasi(6, 5, 4);

        $simulasiId = Simulasi::query()->where('tingkat_id', $this->tingkat->id)->value('id');

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$simulasiId.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertOk()
            ->assertJsonPath('data.lulus', true);

        $data = $this->actingAs($this->siswa)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->json('data');

        $kabupaten = collect($data['tingkat'])->firstWhere('tingkat_id', $this->tingkat->id);

        $this->assertTrue($kabupaten['sudah_lulus']);
        $this->assertSame('LULUS', $kabupaten['tahap']);
        $this->assertSame(85.0, (float) $kabupaten['hasil_simulasi_terakhir']['nilai']);
        $this->assertTrue($kabupaten['hasil_simulasi_terakhir']['lulus']);
    }

    public function test_sisa_kuota_simulasi_berkurang_setiap_percobaan(): void
    {
        $this->buatPutaranDenganSyaratTerpenuhi();

        $this->perhitungan->nilai = 30.0;

        Simulasi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'is_aktif' => true,
        ]);

        $this->isiBankSimulasi(40, 40, 40);

        $simulasiId = Simulasi::query()->where('tingkat_id', $this->tingkat->id)->value('id');

        $kuota = fn (): int => (int) collect(
            $this->actingAs($this->siswa)->getJson('/api/dashboard')->json('data.tingkat')
        )->firstWhere('tingkat_id', $this->tingkat->id)['sisa_kuota_simulasi'];

        $this->assertSame(3, $kuota());

        $hasilId = $this->actingAs($this->siswa)
            ->postJson('/api/simulasi/'.$simulasiId.'/mulai')
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->siswa)
            ->postJson('/api/hasil-simulasi/'.$hasilId.'/submit')
            ->assertOk();

        $this->assertSame(2, $kuota());
    }

    /**
     * Putaran aktif + materi wajib selesai dan latihan lolos batas.
     */
    private function buatPutaranDenganSyaratTerpenuhi(): Pretest
    {
        $pretest = Pretest::factory()->create([
            'user_id' => $this->siswa->id,
            'tingkat_id' => $this->tingkat->id,
            'nilai' => 60.0,
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
        ]);

        $materi = Materi::query()->where('tingkat_id', $this->tingkat->id)->get();

        foreach ($materi as $urutan => $satu) {
            RekomendasiMateri::create([
                'pretest_id' => $pretest->id,
                'user_id' => $this->siswa->id,
                'materi_id' => $satu->id,
                'prioritas' => $urutan + 1,
            ]);

            $quiz = Quiz::create([
                'materi_id' => $satu->id,
                'nama_quiz' => 'Quiz '.$satu->judul,
                'jumlah_soal' => 10,
            ]);

            QuizPengerjaan::create([
                'user_id' => $this->siswa->id,
                'quiz_id' => $quiz->id,
                'nilai' => 80.0,
                'disubmit_pada' => now(),
                'selesai_pada' => now(),
            ]);

            ProgressBelajar::create([
                'user_id' => $this->siswa->id,
                'materi_id' => $satu->id,
                'status' => 'selesai',
                'persentase' => 100,
                'tanggal_selesai' => now(),
            ]);
        }

        return $pretest;
    }

    private function isiBankSimulasi(int $mudah, int $sedang, int $sulit): void
    {
        $materiId = Materi::query()->where('tingkat_id', $this->tingkat->id)->value('id');

        foreach (['mudah' => $mudah, 'sedang' => $sedang, 'sulit' => $sulit] as $level => $banyak) {
            Soal::factory()->count($banyak)->create([
                'tingkat_id' => $this->tingkat->id,
                'materi_id' => $materiId,
                'peruntukan' => 'simulasi',
                'level' => $level,
            ]);
        }
    }
}
