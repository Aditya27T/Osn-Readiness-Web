<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\PretestJawaban;
use App\Models\Quiz;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankSoalApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    private TingkatSeleksi $tingkat;

    private Materi $materi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->siswa = User::factory()->create();
        $this->siswa->assignRole('siswa');

        $this->tingkat = TingkatSeleksi::factory()->create(['urutan' => 1]);
        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $this->tingkat->id]);
        $this->materi = Materi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'kompetensi_id' => $kompetensi->id,
            'urutan' => 1,
        ]);
    }

    /**
     * Bank pre-test per level. Yang dihitung tersedia adalah stok bank,
     * sama dengan kandidat yang dilihat SoalPicker.
     */
    private function bankPretest(int $mudah, int $sedang, int $sulit): void
    {
        foreach (['mudah' => $mudah, 'sedang' => $sedang, 'sulit' => $sulit] as $level => $banyak) {
            Soal::factory()->count($banyak)->create([
                'tingkat_id' => $this->tingkat->id,
                'materi_id' => $this->materi->id,
                'peruntukan' => 'pretest',
                'level' => $level,
            ]);
        }
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/admin/bank-soal/kecukupan?tingkat_id='.$this->tingkat->id)->assertUnauthorized();
    }

    public function test_siswa_dilarang_melihat_laporan(): void
    {
        $this->actingAs($this->siswa)
            ->getJson('/api/admin/bank-soal/kecukupan?tingkat_id='.$this->tingkat->id)
            ->assertForbidden();
    }

    public function test_lima_bagian_laporan_dengan_bank_kosong(): void
    {
        $data = $this->actingAs($this->admin)
            ->getJson('/api/admin/bank-soal/kecukupan?tingkat_id='.$this->tingkat->id)
            ->assertOk()
            ->json('data');

        // Kuota pre-test 30 soal dengan 50/30/20 => 15/9/6.
        $this->assertCount(3, $data['pretest_per_level']);
        $this->assertSame('mudah', $data['pretest_per_level'][0]['level']);
        $this->assertSame(0, $data['pretest_per_level'][0]['tersedia']);
        $this->assertSame(15, $data['pretest_per_level'][0]['kuota']);
        $this->assertTrue($data['pretest_per_level'][0]['kurang']);

        $this->assertSame($this->materi->id, $data['pretest_per_materi'][0]['materi_id']);
        $this->assertSame(2, $data['pretest_per_materi'][0]['minimal']);
        $this->assertTrue($data['pretest_per_materi'][0]['kurang']);

        $this->assertSame(0, $data['putaran_pretest']['putaran']);
        $this->assertSame(2, $data['putaran_pretest']['ambang']);
        $this->assertTrue($data['putaran_pretest']['kurang']);

        $this->assertSame([], $data['simulasi_per_level']);

        $this->assertFalse($data['latihan_per_materi'][0]['punya_latihan']);
        $this->assertTrue($data['latihan_per_materi'][0]['kurang']);
    }

    public function test_bank_cukup_melaporkan_tidak_kurang_dan_putaran_dua(): void
    {
        // Kuota 15/9/6; bank cukup untuk tepat dua putaran tanpa soal
        // berulang.
        $this->bankPretest(30, 18, 12);

        $data = $this->actingAs($this->admin)
            ->getJson('/api/admin/bank-soal/kecukupan?tingkat_id='.$this->tingkat->id)
            ->json('data');

        $this->assertSame(30, $data['pretest_per_level'][0]['tersedia']);
        $this->assertFalse($data['pretest_per_level'][0]['kurang']);
        $this->assertFalse($data['pretest_per_level'][1]['kurang']);
        $this->assertFalse($data['pretest_per_level'][2]['kurang']);

        $this->assertSame(2, $data['putaran_pretest']['putaran']);
        $this->assertFalse($data['putaran_pretest']['kurang']);

        $this->assertFalse($data['pretest_per_materi'][0]['kurang']);
    }

    public function test_soal_yang_dipakai_siswa_lain_tetap_dihitung_tersedia(): void
    {
        $this->bankPretest(30, 18, 12);

        // Satu siswa sudah mengerjakan pre-test dengan seluruh soal mudah.
        // Larangan mengulang berlaku per siswa, jadi siswa berikutnya tetap
        // bisa memakai soal yang sama dan laporan tidak boleh menyusut.
        $pretest = Pretest::factory()->create([
            'user_id' => $this->siswa->id,
            'tingkat_id' => $this->tingkat->id,
        ]);

        $dipakai = Soal::query()
            ->where('tingkat_id', $this->tingkat->id)
            ->where('peruntukan', 'pretest')
            ->where('level', 'mudah')
            ->pluck('id');

        foreach ($dipakai as $urutan => $soalId) {
            PretestJawaban::create([
                'pretest_id' => $pretest->id,
                'soal_id' => $soalId,
                'urutan' => $urutan + 1,
                'bobot' => 1,
            ]);
        }

        $data = $this->actingAs($this->admin)
            ->getJson('/api/admin/bank-soal/kecukupan?tingkat_id='.$this->tingkat->id)
            ->json('data');

        $this->assertSame(30, $data['pretest_per_level'][0]['tersedia']);
        $this->assertFalse($data['pretest_per_level'][0]['kurang']);
        $this->assertSame(2, $data['putaran_pretest']['putaran']);
        $this->assertFalse($data['putaran_pretest']['kurang']);
    }

    public function test_tanpa_tingkat_id_dibalas_422(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/bank-soal/kecukupan')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tingkat_id');

        $this->actingAs($this->admin)
            ->getJson('/api/admin/bank-soal/kecukupan?tingkat_id=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tingkat_id');
    }

    public function test_bagian_simulasi_menampilkan_kuota_tiap_simulasi(): void
    {
        $this->bankPretest(0, 0, 0);

        Simulasi::create([
            'tingkat_id' => $this->tingkat->id,
            'nama_simulasi' => 'Simulasi Kabupaten',
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'is_aktif' => false,
        ]);

        // Bank simulasi: 6 mudah, 3 sedang, 1 sulit — kurang sulit.
        foreach (['mudah' => 6, 'sedang' => 3, 'sulit' => 1] as $level => $banyak) {
            Soal::factory()->count($banyak)->create([
                'tingkat_id' => $this->tingkat->id,
                'materi_id' => $this->materi->id,
                'peruntukan' => 'simulasi',
                'level' => $level,
            ]);
        }

        $data = $this->actingAs($this->admin)
            ->getJson('/api/admin/bank-soal/kecukupan?tingkat_id='.$this->tingkat->id)
            ->json('data');

        $this->assertCount(1, $data['simulasi_per_level']);
        $perLevel = $data['simulasi_per_level'][0]['per_level'];

        // Kuota 30 soal dengan persen simulasi 30/40/30 => 3/4/3.
        $this->assertSame(3, $perLevel[0]['kuota']);
        $this->assertSame(6, $perLevel[0]['tersedia']);
        $this->assertFalse($perLevel[0]['kurang']);

        $this->assertSame(4, $perLevel[1]['kuota']);
        $this->assertSame(3, $perLevel[1]['tersedia']);
        $this->assertTrue($perLevel[1]['kurang']);

        $this->assertSame(1, $perLevel[2]['tersedia']);
        $this->assertSame(3, $perLevel[2]['kuota']);
        $this->assertTrue($perLevel[2]['kurang']);
    }

    public function test_bagian_latihan_menandai_materi_tanpa_quiz(): void
    {
        Quiz::create([
            'materi_id' => $this->materi->id,
            'nama_quiz' => 'Quiz materi',
            'jumlah_soal' => 10,
        ]);

        foreach (['mudah' => 6, 'sedang' => 3, 'sulit' => 1] as $level => $banyak) {
            Soal::factory()->count($banyak)->create([
                'tingkat_id' => $this->tingkat->id,
                'materi_id' => $this->materi->id,
                'peruntukan' => 'latihan',
                'level' => $level,
            ]);
        }

        $data = $this->actingAs($this->admin)
            ->getJson('/api/admin/bank-soal/kecukupan?tingkat_id='.$this->tingkat->id)
            ->json('data');

        $this->assertTrue($data['latihan_per_materi'][0]['punya_latihan']);
        $this->assertSame(10, $data['latihan_per_materi'][0]['dibutuhkan']);
        $this->assertSame(10, $data['latihan_per_materi'][0]['tersedia']);
        $this->assertFalse($data['latihan_per_materi'][0]['kurang']);
    }
}
