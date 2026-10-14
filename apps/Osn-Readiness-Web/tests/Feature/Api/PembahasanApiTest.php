<?php

namespace Tests\Feature\Api;

use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Pembahasan;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembahasanApiTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Soal $soal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed('RolesAndPermissionsSeeder');

        $this->superAdmin = User::factory()->create(['email' => 'super@admin.com']);
        $this->superAdmin->assignRole('Super Admin');

        $tingkat = TingkatSeleksi::factory()->create();
        $kompetensi = Kompetensi::factory()->create();
        $materi = Materi::factory()->create([
            'tingkat_id' => $tingkat->id,
            'kompetensi_id' => $kompetensi->id,
        ]);

        $this->soal = Soal::factory()->create(['materi_id' => $materi->id]);
    }

    public function test_can_show_pembahasan(): void
    {
        Pembahasan::factory()->create(['soal_id' => $this->soal->id]);

        $this->actingAs($this->superAdmin)
            ->getJson("api/admin/soal/{$this->soal->id}/pembahasan")
            ->assertOk()
            ->assertJsonPath('data.soal_id', $this->soal->id);
    }

    public function test_show_pembahasan_not_found(): void
    {
        $this->actingAs($this->superAdmin)
            ->getJson("api/admin/soal/{$this->soal->id}/pembahasan")
            ->assertNotFound();
    }

    public function test_can_create_pembahasan(): void
    {
        $data = [
            'isi_pembahasan' => 'Ini adalah pembahasan soal',
        ];

        $this->actingAs($this->superAdmin)
            ->postJson("api/admin/soal/{$this->soal->id}/pembahasan", $data)
            ->assertCreated()
            ->assertJsonPath('data.isi_pembahasan', $data['isi_pembahasan']);

        $this->assertDatabaseHas('pembahasan', [
            'soal_id' => $this->soal->id,
            'isi_pembahasan' => $data['isi_pembahasan'],
        ]);
    }

    public function test_can_update_pembahasan(): void
    {
        $pembahasan = Pembahasan::factory()->create(['soal_id' => $this->soal->id]);

        $updateData = [
            'isi_pembahasan' => 'Pembahasan yang diperbarui',
        ];

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/soal/{$this->soal->id}/pembahasan", $updateData)
            ->assertOk()
            ->assertJsonPath('data.isi_pembahasan', $updateData['isi_pembahasan']);
    }

    public function test_can_delete_pembahasan(): void
    {
        Pembahasan::factory()->create(['soal_id' => $this->soal->id]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("api/admin/soal/{$this->soal->id}/pembahasan")
            ->assertOk();

        $this->assertDatabaseMissing('pembahasan', ['soal_id' => $this->soal->id]);
    }

    public function test_simpan_pembahasan_dua_kali_tetap_satu_baris(): void
    {
        foreach (['Versi pertama', 'Versi kedua'] as $isi) {
            $this->actingAs($this->superAdmin)
                ->postJson("api/admin/soal/{$this->soal->id}/pembahasan", ['isi_pembahasan' => $isi])
                ->assertCreated();
        }

        $this->assertDatabaseCount('pembahasan', 1);
        $this->assertDatabaseHas('pembahasan', ['soal_id' => $this->soal->id, 'isi_pembahasan' => 'Versi kedua']);
    }

    public function test_delete_pembahasan_not_found(): void
    {
        $this->actingAs($this->superAdmin)
            ->deleteJson("api/admin/soal/{$this->soal->id}/pembahasan")
            ->assertNotFound();
    }
}
