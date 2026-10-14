<?php

namespace Tests\Feature\Jobs;

use App\Jobs\PurgeAkunJob;
use App\Models\HasilSimulasi;
use App\Models\KenaikanTingkat;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\ProgressBelajar;
use App\Models\Quiz;
use App\Models\QuizJawaban;
use App\Models\QuizPengerjaan;
use App\Models\Simulasi;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeAkunJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function akun_yang_baru_dihapus_tidak_disentuh(): void
    {
        $user = User::factory()->create(['deleted_at' => now()->subDays(5)]);

        (new PurgeAkunJob)->handle();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    #[Test]
    public function akun_yang_melewati_masa_retensi_dihapus_permanen(): void
    {
        $user = User::factory()->create(['deleted_at' => now()->subDays(31)]);

        (new PurgeAkunJob)->handle();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertNull(User::withTrashed()->find($user->id));
    }

    #[Test]
    public function batas_retensi_tepat_dijalankan(): void
    {
        // Tepat di hari ke-30 belum dihapus, hari ke-31 sudah.
        $tepatBatas = User::factory()->create(['deleted_at' => now()->subDays(30)]);
        $lewatBatas = User::factory()->create(['deleted_at' => now()->subDays(31)]);

        (new PurgeAkunJob)->handle();

        $this->assertSoftDeleted('users', ['id' => $tepatBatas->id]);
        $this->assertDatabaseMissing('users', ['id' => $lewatBatas->id]);
    }

    #[Test]
    public function akun_aktif_tidak_pernah_disentuh(): void
    {
        $user = User::factory()->create(['deleted_at' => null]);

        (new PurgeAkunJob)->handle();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    #[Test]
    public function data_pengerjaan_ikut_terhapus(): void
    {
        $user = User::factory()->create(['deleted_at' => now()->subDays(40)]);
        $tingkat = TingkatSeleksi::factory()->create();
        $materi = Materi::factory()->create(['tingkat_id' => $tingkat->id]);

        $pretest = Pretest::factory()->selesai()->create([
            'user_id' => $user->id,
            'tingkat_id' => $tingkat->id,
        ]);

        $quiz = Quiz::factory()->create(['materi_id' => $materi->id]);
        $pengerjaan = QuizPengerjaan::factory()->selesai()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);
        $quizJawaban = QuizJawaban::factory()->create([
            'pengerjaan_id' => $pengerjaan->id,
        ]);

        $simulasi = HasilSimulasi::factory()->selesai()->create([
            'user_id' => $user->id,
            'simulasi_id' => Simulasi::factory()->create(['tingkat_id' => $tingkat->id])->id,
            'pretest_id' => $pretest->id,
        ]);

        $progress = ProgressBelajar::factory()->selesai()->create([
            'user_id' => $user->id,
            'materi_id' => $materi->id,
        ]);

        $kenaikan = KenaikanTingkat::factory()->lulus()->create([
            'user_id' => $user->id,
            'tingkat_asal_id' => $tingkat->id,
        ]);

        (new PurgeAkunJob)->handle();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('pretest', ['id' => $pretest->id]);
        $this->assertDatabaseMissing('quiz_pengerjaan', ['id' => $pengerjaan->id]);
        $this->assertDatabaseMissing('quiz_jawaban', ['id' => $quizJawaban->id]);
        $this->assertDatabaseMissing('hasil_simulasi', ['id' => $simulasi->id]);
        $this->assertDatabaseMissing('progress_belajar', ['id' => $progress->id]);
        $this->assertDatabaseMissing('kenaikan_tingkat', ['id' => $kenaikan->id]);
    }

    #[Test]
    public function akun_lain_tidak_ikut_terhapus(): void
    {
        $ketinggalan = User::factory()->create(['deleted_at' => now()->subDays(40)]);
        $terlama = User::factory()->create(['deleted_at' => now()->subDays(60)]);
        $aktif = User::factory()->create(['deleted_at' => null]);
        $baru = User::factory()->create(['deleted_at' => now()->subDays(2)]);

        (new PurgeAkunJob)->handle();

        $this->assertDatabaseMissing('users', ['id' => $ketinggalan->id]);
        $this->assertDatabaseMissing('users', ['id' => $terlama->id]);
        $this->assertDatabaseHas('users', ['id' => $aktif->id]);
        $this->assertSoftDeleted('users', ['id' => $baru->id]);
    }

    #[Test]
    public function masa_retensi_bisa_diubah_lewat_env(): void
    {
        config(['osn.retensi_akun_hari' => 7]);

        $baru = User::factory()->create(['deleted_at' => now()->subDays(10)]);

        (new PurgeAkunJob)->handle();

        $this->assertDatabaseMissing('users', ['id' => $baru->id]);
    }

    #[Test]
    public function nilai_bawaan_masa_retensi_adalah_tiga_puluh_hari(): void
    {
        $this->assertSame(30, (int) config('osn.retensi_akun_hari'));
    }

    #[Test]
    public function berjalan_berulang_tidak_meniang(): void
    {
        $user = User::factory()->create(['deleted_at' => now()->subDays(40)]);

        (new PurgeAkunJob)->handle();
        (new PurgeAkunJob)->handle();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function banyak_akun_dihapus_dalam_beberapa_potongan(): void
    {
        config(['osn.purge_akun_chunk' => 10]);

        $ids = [];

        for ($i = 0; $i < 25; $i++) {
            $ids[] = User::factory()->create(['deleted_at' => now()->subDays(40)])->id;
        }

        (new PurgeAkunJob)->handle();

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('users', ['id' => $id]);
        }
    }

    #[Test]
    public function job_terjadwal_setiap_hari_pada_pukul_tiga_pagi(): void
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('App\\Jobs\\PurgeAkunJob', $output);
        $this->assertStringContainsString('0 3 * * *', $output);
    }

    #[Test]
    public function job_memakai_without_overlapping(): void
    {
        // Tanda ⇁ muncul di schedule:list -v bila mutex aktif, supaya dua
        // siklus purge tidak berjalan bersamaan.
        Artisan::call('schedule:list', ['-v' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('⇁', $output);
        $this->assertStringContainsString('PurgeAkunJob', $output);
    }

    #[Test]
    public function job_bisa_dimasukkan_ke_queue(): void
    {
        Queue::fake();

        PurgeAkunJob::dispatch();

        Queue::assertPushed(PurgeAkunJob::class);
    }
}
