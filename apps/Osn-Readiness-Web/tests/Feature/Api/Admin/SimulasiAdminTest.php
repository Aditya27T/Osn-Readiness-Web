<?php

namespace Tests\Feature\Api\Admin;

use App\Models\HasilSimulasi;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulasiAdminTest extends TestCase
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

        $this->tingkat = TingkatSeleksi::factory()->create();
        $this->materi = Materi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'kompetensi_id' => Kompetensi::factory()->create(['tingkat_id' => $this->tingkat->id])->id,
        ]);
    }

    /**
     * Kuota 10 soal simulasi dengan pembagian 30/40/30 adalah 3/4/3.
     */
    private function isiBank(int $mudah = 3, int $sedang = 4, int $sulit = 3): void
    {
        foreach (['mudah' => $mudah, 'sedang' => $sedang, 'sulit' => $sulit] as $level => $banyak) {
            Soal::factory()->count($banyak)->create([
                'tingkat_id' => $this->tingkat->id,
                'materi_id' => $this->materi->id,
                'peruntukan' => 'simulasi',
                'level' => $level,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function payload(array $ubah = []): array
    {
        return array_merge([
            'tingkat_id' => $this->tingkat->id,
            'nama_simulasi' => 'Simulasi 1',
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'is_aktif' => false,
        ], $ubah);
    }

    private function simulasi(bool $aktif = false): Simulasi
    {
        return Simulasi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'jumlah_soal' => 10,
            'durasi_menit' => 60,
            'is_aktif' => $aktif,
        ]);
    }

    public function test_tanpa_token_dibalas_401(): void
    {
        $this->getJson('/api/admin/simulasi')->assertUnauthorized();
    }

    public function test_siswa_dibalas_403(): void
    {
        $this->actingAs($this->siswa)->getJson('/api/admin/simulasi')->assertForbidden();
        $this->actingAs($this->siswa)->postJson('/api/admin/simulasi', $this->payload())->assertForbidden();
    }

    public function test_daftar_memuat_simulasi_nonaktif_dan_detail(): void
    {
        $simulasi = $this->simulasi(aktif: false);

        $this->actingAs($this->admin)->getJson('/api/admin/simulasi')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->admin)->getJson("/api/admin/simulasi/{$simulasi->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $simulasi->id);
    }

    public function test_jumlah_soal_dan_durasi_harus_lebih_dari_nol(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/simulasi', $this->payload(['jumlah_soal' => 0, 'durasi_menit' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jumlah_soal', 'durasi_menit']);
    }

    public function test_tambah_simulasi_nonaktif_tidak_memeriksa_bank(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/simulasi', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.nama_simulasi', 'Simulasi 1');
    }

    public function test_menyalakan_ditolak_saat_bank_kurang(): void
    {
        $this->isiBank(mudah: 3, sedang: 3, sulit: 3);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/simulasi', $this->payload(['is_aktif' => true]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_aktif');

        $simulasi = $this->simulasi(aktif: false);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/simulasi/{$simulasi->id}", $this->payload(['is_aktif' => true]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_aktif');

        $this->assertFalse((bool) $simulasi->fresh()->is_aktif);
    }

    public function test_menyalakan_lolos_saat_bank_cukup(): void
    {
        $this->isiBank();
        $simulasi = $this->simulasi(aktif: false);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/simulasi/{$simulasi->id}", $this->payload(['is_aktif' => true]))
            ->assertOk()
            ->assertJsonPath('data.is_aktif', true);
    }

    public function test_mematikan_dan_mengganti_nama_selalu_boleh_walau_bank_kurang(): void
    {
        $simulasi = $this->simulasi(aktif: true);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/simulasi/{$simulasi->id}", $this->payload(['is_aktif' => true, 'nama_simulasi' => 'Nama baru']))
            ->assertOk()
            ->assertJsonPath('data.nama_simulasi', 'Nama baru');

        $this->actingAs($this->admin)
            ->putJson("/api/admin/simulasi/{$simulasi->id}", $this->payload(['is_aktif' => false]))
            ->assertOk()
            ->assertJsonPath('data.is_aktif', false);
    }

    public function test_menambah_jumlah_soal_simulasi_aktif_memeriksa_bank(): void
    {
        $this->isiBank();
        $simulasi = $this->simulasi(aktif: true);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/simulasi/{$simulasi->id}", $this->payload(['is_aktif' => true, 'jumlah_soal' => 20]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_aktif');
    }

    public function test_hapus_simulasi(): void
    {
        $simulasi = $this->simulasi();

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/simulasi/{$simulasi->id}")
            ->assertOk();

        $this->assertDatabaseMissing('simulasi', ['id' => $simulasi->id]);
    }

    public function test_hapus_simulasi_yang_punya_hasil_dibalas_409(): void
    {
        $simulasi = $this->simulasi();
        HasilSimulasi::factory()->create(['simulasi_id' => $simulasi->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/simulasi/{$simulasi->id}")
            ->assertStatus(409)
            ->assertJsonPath('kode', 'SIMULASI_MASIH_DIGUNAKAN');

        $this->assertDatabaseHas('simulasi', ['id' => $simulasi->id]);
    }
}
