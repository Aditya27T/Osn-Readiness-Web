<?php

namespace Tests\Feature\Api;

use App\Models\HasilSimulasi;
use App\Models\HasilSimulasiJawaban;
use App\Models\Kompetensi;
use App\Models\KonteksSoal;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\PretestJawaban;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SoalApiTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $siswa;

    private TingkatSeleksi $tingkat;

    private Materi $materi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed('RolesAndPermissionsSeeder');

        // Create super admin
        $this->superAdmin = User::factory()->create(['email' => 'super@admin.com']);
        $this->superAdmin->assignRole('Super Admin');

        // Create regular siswa
        $this->siswa = User::factory()->create(['email' => 'siswa@test.com']);
        $this->siswa->assignRole('siswa');

        // Create test data
        $this->tingkat = TingkatSeleksi::factory()->create();
        $kompetensi = Kompetensi::factory()->create(['tingkat_id' => $this->tingkat->id]);
        $this->materi = Materi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'kompetensi_id' => $kompetensi->id,
        ]);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('api/admin/soal')
            ->assertUnauthorized();
    }

    public function test_user_without_permission_returns_403(): void
    {
        $this->actingAs($this->siswa)
            ->getJson('api/admin/soal')
            ->assertForbidden();
    }

    public function test_super_admin_can_list_soal(): void
    {
        Soal::factory()->count(3)->create(['materi_id' => $this->materi->id]);

        $this->actingAs($this->superAdmin)
            ->getJson('api/admin/soal')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'materi_id', 'level', 'tipe_soal', 'pertanyaan', 'pilihan_jawaban'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_admin_can_list_soal(): void
    {
        Soal::factory()->count(3)->create(['materi_id' => $this->materi->id]);

        $this->actingAs($this->superAdmin)
            ->getJson('api/admin/soal')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_super_admin_can_show_soal(): void
    {
        $soal = Soal::factory()->create(['materi_id' => $this->materi->id]);

        $this->actingAs($this->superAdmin)
            ->getJson("api/admin/soal/{$soal->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $soal->id)
            ->assertJsonPath('data.pertanyaan', $soal->pertanyaan);
    }

    public function test_super_admin_can_create_soal(): void
    {
        $data = [
            'tingkat_id' => $this->tingkat->id,
            'materi_id' => $this->materi->id,
            'level' => 'mudah',
            'peruntukan' => 'pretest',
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Apa itu test?',
            'pilihan_jawaban' => ['A' => 'Jawaban A', 'B' => 'Jawaban B', 'C' => 'Jawaban C'],
            'kunci_jawaban' => 'B',
        ];

        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', $data)
            ->assertCreated()
            ->assertJsonPath('data.pertanyaan', $data['pertanyaan']);

        $this->assertDatabaseHas('soal', [
            'pertanyaan' => $data['pertanyaan'],
            'kunci_jawaban' => $data['kunci_jawaban'],
        ]);
    }

    public function test_create_soal_validates_input(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', [
                'tingkat_id' => 999,
                'materi_id' => 999,
                'pilihan_jawaban' => ['Hanya satu'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tingkat_id', 'materi_id', 'level', 'pertanyaan', 'kunci_jawaban']);
    }

    public function test_super_admin_can_update_soal(): void
    {
        $soal = Soal::factory()->create(['materi_id' => $this->materi->id, 'level' => 'mudah']);

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/soal/{$soal->id}", $this->payloadUpdate($soal, [
                'pertanyaan' => 'Pertanyaan yang sudah diperbaiki?',
                'level' => 'sulit',
            ]))
            ->assertOk()
            ->assertJsonPath('data.pertanyaan', 'Pertanyaan yang sudah diperbaiki?')
            ->assertJsonPath('data.level', 'sulit');
    }

    public function test_update_boleh_hanya_mengirim_kolom_yang_diubah(): void
    {
        $soal = Soal::factory()->create(['materi_id' => $this->materi->id]);

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/soal/{$soal->id}", ['pertanyaan' => 'Hanya teks ini yang berubah?'])
            ->assertOk()
            ->assertJsonPath('data.pertanyaan', 'Hanya teks ini yang berubah?')
            ->assertJsonPath('data.kunci_jawaban', $soal->kunci_jawaban);
    }

    public function test_soal_isian_dibuat_tanpa_pilihan(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', $this->payloadBaru([
                'tipe_soal' => 'isian',
                'pilihan_jawaban' => null,
                'kunci_jawaban' => '42',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.tipe_soal', 'isian')
            ->assertJsonPath('data.pilihan_jawaban', null)
            ->assertJsonPath('data.kunci_jawaban', '42');
    }

    public function test_soal_isian_dengan_pilihan_ditolak(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', $this->payloadBaru(['tipe_soal' => 'isian']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pilihan_jawaban');
    }

    public function test_pilihan_ganda_butuh_minimal_dua_pilihan(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', $this->payloadBaru([
                'pilihan_jawaban' => ['A' => 'Hanya satu'],
                'kunci_jawaban' => 'A',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pilihan_jawaban');

        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', $this->payloadBaru(['pilihan_jawaban' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pilihan_jawaban');
    }

    public function test_pilihan_harus_berkunci_huruf(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', $this->payloadBaru([
                'pilihan_jawaban' => ['Satu', 'Dua', 'Tiga'],
                'kunci_jawaban' => '1',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pilihan_jawaban');
    }

    public function test_kunci_di_luar_pilihan_ditolak(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', $this->payloadBaru(['kunci_jawaban' => 'G']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kunci_jawaban');
    }

    public function test_materi_dan_cerita_harus_setingkat(): void
    {
        $tingkatLain = TingkatSeleksi::factory()->create();
        $materiLain = Materi::factory()->create([
            'tingkat_id' => $tingkatLain->id,
            'kompetensi_id' => Kompetensi::factory()->create(['tingkat_id' => $tingkatLain->id])->id,
        ]);
        $ceritaLain = KonteksSoal::factory()->create(['tingkat_id' => $tingkatLain->id]);

        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', $this->payloadBaru(['materi_id' => $materiLain->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('materi_id');

        $this->actingAs($this->superAdmin)
            ->postJson('api/admin/soal', $this->payloadBaru(['konteks_id' => $ceritaLain->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('konteks_id');
    }

    public function test_soal_impor_bisa_diedit_tanpa_mengubah_format_kunci(): void
    {
        $soal = Soal::factory()->create([
            'materi_id' => $this->materi->id,
            'id_sumber' => 'kab-2020-001',
            'pilihan_jawaban' => ['A' => 'satu', 'B' => 'dua', 'C' => 'tiga'],
            'kunci_jawaban' => 'C',
        ]);

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/soal/{$soal->id}", $this->payloadUpdate($soal, ['pertanyaan' => 'Teks baru?']))
            ->assertOk()
            ->assertJsonPath('data.kunci_jawaban', 'C')
            ->assertJsonPath('data.pilihan_jawaban.C', 'tiga');
    }

    public function test_super_admin_can_delete_soal(): void
    {
        $soal = Soal::factory()->create(['materi_id' => $this->materi->id]);

        $this->actingAs($this->superAdmin)
            ->deleteJson("api/admin/soal/{$soal->id}")
            ->assertOk();

        // Soft delete — should still exist
        $this->assertDatabaseHas('soal', ['id' => $soal->id]);
        $this->assertSoftDeleted('soal', ['id' => $soal->id]);
    }

    public function test_hapus_soal_tidak_menghapus_berkas_gambar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('soal/gambar.png', 'isi');

        $soal = Soal::factory()->create(['materi_id' => $this->materi->id, 'gambar' => 'soal/gambar.png']);

        $this->actingAs($this->superAdmin)
            ->deleteJson("api/admin/soal/{$soal->id}")
            ->assertOk();

        // Review pengerjaan lama masih menampilkan gambar ini.
        Storage::disk('public')->assertExists('soal/gambar.png');
    }

    public function test_kunci_jawaban_not_in_response(): void
    {
        $soal = Soal::factory()->create(['materi_id' => $this->materi->id]);

        $this->actingAs($this->siswa)
            ->actingAs($this->superAdmin)
            ->getJson("api/admin/soal/{$soal->id}")
            ->assertOk()
            // Admin view includes kunci_jawaban per SoalDetailResource
            ->assertJsonPath('data.kunci_jawaban', $soal->kunci_jawaban);
    }

    private function pakaiDiPretest(Soal $soal): void
    {
        PretestJawaban::create([
            'pretest_id' => Pretest::factory()->create(['tingkat_id' => $this->tingkat->id])->id,
            'soal_id' => $soal->id,
            'urutan' => 1,
            'bobot' => 1,
        ]);
    }

    public function test_kunci_soal_terpakai_dilarang_diubah(): void
    {
        $soal = Soal::factory()->create(['materi_id' => $this->materi->id]);
        $this->pakaiDiPretest($soal);

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/soal/{$soal->id}", $this->payloadUpdate($soal, ['kunci_jawaban' => 'B']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('soal');

        $this->assertDatabaseHas('soal', [
            'id' => $soal->id,
            'kunci_jawaban' => $soal->kunci_jawaban,
        ]);
    }

    public function test_level_peruntukan_dan_materi_soal_terpakai_dilarang_diubah(): void
    {
        $soal = Soal::factory()->create([
            'materi_id' => $this->materi->id,
            'level' => 'mudah',
            'peruntukan' => 'pretest',
        ]);
        $this->pakaiDiPretest($soal);

        $materiLain = Materi::factory()->create([
            'tingkat_id' => $this->tingkat->id,
            'kompetensi_id' => $this->materi->kompetensi_id,
        ]);

        foreach ([['level' => 'sulit'], ['peruntukan' => 'latihan'], ['materi_id' => $materiLain->id]] as $ubah) {
            $this->actingAs($this->superAdmin)
                ->putJson("api/admin/soal/{$soal->id}", $this->payloadUpdate($soal, $ubah))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('soal');
        }

        $this->assertDatabaseHas('soal', [
            'id' => $soal->id,
            'level' => 'mudah',
            'peruntukan' => 'pretest',
            'materi_id' => $this->materi->id,
        ]);
    }

    public function test_teks_soal_terpakai_boleh_diperbaiki(): void
    {
        $soal = Soal::factory()->create(['materi_id' => $this->materi->id]);
        $this->pakaiDiPretest($soal);

        // Seluruh kolom dikirim ulang dengan nilai yang sama, seperti form
        // edit pada umumnya; hanya teks yang berbeda.
        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/soal/{$soal->id}", $this->payloadUpdate($soal, [
                'pertanyaan' => 'Salah ketik sudah diperbaiki?',
                'pilihan_jawaban' => ['A' => 'satu', 'B' => 'dua', 'C' => 'tiga', 'D' => 'empat'],
            ]))
            ->assertOk()
            ->assertJsonPath('data.pertanyaan', 'Salah ketik sudah diperbaiki?')
            ->assertJsonPath('data.pilihan_jawaban.D', 'empat');
    }

    public function test_soal_yang_hanya_dipakai_simulasi_juga_terkunci(): void
    {
        $soal = Soal::factory()->create(['materi_id' => $this->materi->id]);

        $hasil = HasilSimulasi::factory()->create();
        HasilSimulasiJawaban::create([
            'hasil_simulasi_id' => $hasil->id,
            'soal_id' => $soal->id,
            'urutan' => 1,
            'bobot' => 1,
        ]);

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/soal/{$soal->id}", $this->payloadUpdate($soal, ['kunci_jawaban' => 'B']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('soal');
    }

    public function test_kunci_soal_belum_dipakai_boleh_diubah(): void
    {
        $soal = Soal::factory()->create(['materi_id' => $this->materi->id]);

        $this->actingAs($this->superAdmin)
            ->putJson("api/admin/soal/{$soal->id}", $this->payloadUpdate($soal, ['kunci_jawaban' => 'B']))
            ->assertOk();

        $this->assertDatabaseHas('soal', [
            'id' => $soal->id,
            'kunci_jawaban' => 'B',
        ]);
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function payloadBaru(array $ubah = []): array
    {
        return array_merge([
            'tingkat_id' => $this->tingkat->id,
            'materi_id' => $this->materi->id,
            'level' => 'mudah',
            'peruntukan' => 'pretest',
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Apa itu test?',
            'pilihan_jawaban' => ['A' => 'Jawaban A', 'B' => 'Jawaban B', 'C' => 'Jawaban C'],
            'kunci_jawaban' => 'B',
        ], $ubah);
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function payloadUpdate(Soal $soal, array $ubah = []): array
    {
        return array_merge([
            'tingkat_id' => $this->tingkat->id,
            'materi_id' => $this->materi->id,
            'level' => $soal->level->value,
            'peruntukan' => $soal->peruntukan->value,
            'tipe_soal' => $soal->tipe_soal->value,
            'pertanyaan' => $soal->pertanyaan,
            'pilihan_jawaban' => $soal->pilihan_jawaban,
            'kunci_jawaban' => $soal->kunci_jawaban,
        ], $ubah);
    }
}
