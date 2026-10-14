<?php

namespace Tests\Feature\Api;

use App\Models\AturanPemetaan;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AturanPemetaanApiTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private TingkatSeleksi $tingkat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed('RolesAndPermissionsSeeder');

        $this->superAdmin = User::factory()->create(['email' => 'super@admin.com']);
        $this->superAdmin->assignRole('Super Admin');

        $this->tingkat = TingkatSeleksi::factory()->create();
    }

    /**
     * Nilai default yang benar menurut aturan bisnis, dipakai untuk payload
     * update yang valid supaya test fokus pada satu perubahan tiap kasus.
     *
     * @return array<string, int>
     */
    private function nilaiValid(): array
    {
        return [
            'bobot_mudah' => 1,
            'bobot_sedang' => 2,
            'bobot_sulit' => 3,
            'pretest_jumlah_soal' => 30,
            'pretest_persen_mudah' => 50,
            'pretest_persen_sedang' => 30,
            'pretest_persen_sulit' => 20,
            'pretest_min_soal_per_materi' => 2,
            'jumlah_materi_wajib' => 1,
            'latihan_min_soal' => 10,
            'latihan_min_nilai' => 50,
            'simulasi_persen_mudah' => 30,
            'simulasi_persen_sedang' => 40,
            'simulasi_persen_sulit' => 30,
            'simulasi_maks_percobaan' => 3,
            'passing_grade' => 70,
        ];
    }

    public function test_can_show_aturan_pemetaan(): void
    {
        $this->actingAs($this->superAdmin)
            ->getJson("api/admin/tingkat/{$this->tingkat->id}/aturan-pemetaan")
            ->assertOk()
            ->assertJsonPath('data.tingkat_id', $this->tingkat->id)
            ->assertJsonPath('data.pretest_jumlah_soal', 30)
            ->assertJsonPath('data.passing_grade', 70);
    }

    public function test_show_aturan_pemetaan_not_found(): void
    {
        $tingkatBaru = TingkatSeleksi::factory()->create();
        // Delete the auto-created aturan to test the not-found scenario
        $tingkatBaru->aturanPemetaan()->delete();

        $this->actingAs($this->superAdmin)
            ->getJson("api/admin/tingkat/{$tingkatBaru->id}/aturan-pemetaan")
            ->assertNotFound();
    }

    public function test_can_update_aturan_pemetaan(): void
    {
        $this->buatMateri(3);

        $updateData = $this->nilaiValid();
        $updateData['passing_grade'] = 65;

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/tingkat/{$this->tingkat->id}/aturan-pemetaan", $updateData)
            ->assertOk()
            ->assertJsonPath('data.passing_grade', 65);

        $this->assertDatabaseHas('aturan_pemetaan', [
            'tingkat_id' => $this->tingkat->id,
            'parameter' => 'passing_grade',
            'ketentuan' => '65',
        ]);
    }

    public function test_update_ditolak_bila_persen_pretest_tidak_berjumlah_100(): void
    {
        $updateData = $this->nilaiValid();
        $updateData['pretest_persen_sulit'] = 25; // total 105

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/tingkat/{$this->tingkat->id}/aturan-pemetaan", $updateData)
            ->assertUnprocessable();

        $this->assertDatabaseHas('aturan_pemetaan', [
            'tingkat_id' => $this->tingkat->id,
            'parameter' => 'passing_grade',
            'ketentuan' => '70',
        ]);
    }

    public function test_update_ditolak_bila_persen_simulasi_tidak_berjumlah_100(): void
    {
        $this->buatMateri(3);

        $updateData = $this->nilaiValid();
        $updateData['simulasi_persen_mudah'] = 40; // total 110

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/tingkat/{$this->tingkat->id}/aturan-pemetaan", $updateData)
            ->assertUnprocessable();
    }

    public function test_update_ditolak_bila_jumlah_materi_wajib_melebihi_materi_tingkat(): void
    {
        $this->buatMateri(2);

        $updateData = $this->nilaiValid();
        $updateData['jumlah_materi_wajib'] = 3;

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/tingkat/{$this->tingkat->id}/aturan-pemetaan", $updateData)
            ->assertUnprocessable();
    }

    public function test_update_ditolak_bila_kuota_soal_kurang_dari_kuota_materi(): void
    {
        $this->buatMateri(3);

        $updateData = $this->nilaiValid();
        $updateData['pretest_jumlah_soal'] = 5; // min 2 per materi, cukup 1 materi saja

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/tingkat/{$this->tingkat->id}/aturan-pemetaan", $updateData)
            ->assertUnprocessable();
    }

    public function test_update_validates_numeric(): void
    {
        $this->buatMateri(3);

        $updateData = $this->nilaiValid();
        $updateData['bobot_mudah'] = 'bukan angka';

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/tingkat/{$this->tingkat->id}/aturan-pemetaan", $updateData)
            ->assertUnprocessable();
    }

    public function test_siswa_dilarang_mengubah_aturan(): void
    {
        $this->buatMateri(3);

        $siswa = User::factory()->create();
        $siswa->assignRole('siswa');

        $this->actingAs($siswa)
            ->putJson("api/admin/tingkat/{$this->tingkat->id}/aturan-pemetaan", $this->nilaiValid())
            ->assertForbidden();
    }

    public function test_mengubah_aturan_tidak_menimpa_parameter_lain(): void
    {
        $this->buatMateri(3);

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/tingkat/{$this->tingkat->id}/aturan-pemetaan", $this->nilaiValid());

        $bobotSedangSebelum = AturanPemetaan::query()
            ->where('tingkat_id', $this->tingkat->id)
            ->where('parameter', 'bobot_sedang')
            ->value('ketentuan');

        $this->assertSame('2', $bobotSedangSebelum);
    }

    private function buatMateri(int $jumlah): void
    {
        $kompetensi = Kompetensi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
        ]);

        Materi::factory()->count($jumlah)->create([
            'tingkat_id' => $this->tingkat->id,
            'kompetensi_id' => $kompetensi->id,
        ]);
    }
}
