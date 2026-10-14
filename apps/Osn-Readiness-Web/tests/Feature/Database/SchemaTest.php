<?php

namespace Tests\Feature\Database;

use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Enums\StatusProgress;
use App\Enums\TipeSoal;
use App\Models\HasilSimulasi;
use App\Models\KenaikanTingkat;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\ProgressBelajar;
use App\Models\Quiz;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tables_and_view_exist(): void
    {
        $tabel = [
            'tingkat_seleksi', 'kompetensi', 'materi', 'konteks_soal', 'soal',
            'pembahasan', 'aturan_pemetaan', 'simulasi', 'quiz', 'pretest',
            'pretest_jawaban', 'pemetaan_materi', 'rekomendasi_materi',
            'progress_belajar', 'quiz_pengerjaan', 'quiz_jawaban', 'hasil_simulasi',
            'hasil_simulasi_jawaban', 'kenaikan_tingkat',
        ];

        foreach ($tabel as $nama) {
            $this->assertTrue(Schema::hasTable($nama), "Tabel {$nama} tidak ada.");
        }

        $this->assertTrue(
            DB::selectOne("SELECT to_regclass('public.riwayat_hasil') AS view")->view !== null,
            'View riwayat_hasil tidak ada.',
        );
    }

    public function test_users_email_unik_hanya_untuk_akun_yang_belum_dihapus(): void
    {
        User::factory()->create(['email' => 'siswa@example.com']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'siswa@example.com']);
    }

    public function test_email_yang_sudah_dihapus_bisa_dipakai_lagi(): void
    {
        User::factory()->create(['email' => 'lama@example.com'])->delete();

        $user = User::factory()->create(['email' => 'lama@example.com']);

        $this->assertNull($user->deleted_at);
    }

    public function test_hanya_satu_pretest_berjalan_per_siswa_per_tingkat(): void
    {
        $user = User::factory()->create();
        $tingkat = TingkatSeleksi::factory()->create();

        Pretest::factory()->create([
            'user_id' => $user->id,
            'tingkat_id' => $tingkat->id,
        ]);

        $this->expectException(QueryException::class);
        Pretest::factory()->create([
            'user_id' => $user->id,
            'tingkat_id' => $tingkat->id,
        ]);
    }

    public function test_hanya_satu_simulasi_berjalan_per_siswa(): void
    {
        $user = User::factory()->create();
        $pretest = Pretest::factory()->create(['user_id' => $user->id]);

        HasilSimulasi::factory()->create([
            'user_id' => $user->id,
            'pretest_id' => $pretest->id,
        ]);

        $this->expectException(QueryException::class);
        HasilSimulasi::factory()->create([
            'user_id' => $user->id,
            'pretest_id' => $pretest->id,
        ]);
    }

    public function test_status_lulus_hanya_bisa_satu_kali_per_siswa_per_tingkat(): void
    {
        $user = User::factory()->create();
        $tingkat = TingkatSeleksi::factory()->create();

        KenaikanTingkat::factory()->lulus()->create([
            'user_id' => $user->id,
            'tingkat_asal_id' => $tingkat->id,
        ]);

        $this->expectException(QueryException::class);
        KenaikanTingkat::factory()->lulus()->create([
            'user_id' => $user->id,
            'tingkat_asal_id' => $tingkat->id,
        ]);
    }

    public function test_kenaikan_tidak_lulus_boleh_berulang(): void
    {
        $user = User::factory()->create();
        $tingkat = TingkatSeleksi::factory()->create();

        KenaikanTingkat::factory()->tidakLulus()->count(2)->create([
            'user_id' => $user->id,
            'tingkat_asal_id' => $tingkat->id,
        ]);

        $this->assertSame(2, KenaikanTingkat::where('user_id', $user->id)->count());
    }

    public function test_check_constraint_menolak_level_di_luar_daftar(): void
    {
        $materi = Materi::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('soal')->insert([
            'tingkat_id' => $materi->tingkat_id,
            'materi_id' => $materi->id,
            'level' => 'gampang',
            'peruntukan' => Peruntukan::Pretest->value,
            'tipe_soal' => TipeSoal::Isian->value,
            'pertanyaan' => 'Soal.',
            'kunci_jawaban' => 'x',
        ]);
    }

    public function test_check_constraint_menolak_peruntukan_di_luar_daftar(): void
    {
        $materi = Materi::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('soal')->insert([
            'tingkat_id' => $materi->tingkat_id,
            'materi_id' => $materi->id,
            'level' => Level::Mudah->value,
            'peruntukan' => 'ujian',
            'tipe_soal' => TipeSoal::Isian->value,
            'pertanyaan' => 'Soal.',
            'kunci_jawaban' => 'x',
        ]);
    }

    public function test_quiz_jumlah_soal_harus_sembilan_ke_atas(): void
    {
        $materi = Materi::factory()->create();

        $this->expectException(QueryException::class);
        Quiz::factory()->create(['materi_id' => $materi->id, 'jumlah_soal' => 9]);
    }

    public function test_progress_status_dilindungi_check_constraint(): void
    {
        $this->expectException(QueryException::class);
        DB::table('progress_belajar')->insert([
            'user_id' => User::factory()->create()->id,
            'materi_id' => Materi::factory()->create()->id,
            'status' => 'setengah',
        ]);
    }

    public function test_kenaikan_status_dilindungi_check_constraint(): void
    {
        $this->expectException(QueryException::class);
        DB::table('kenaikan_tingkat')->insert([
            'user_id' => User::factory()->create()->id,
            'tingkat_asal_id' => TingkatSeleksi::factory()->create()->id,
            'status' => 'gagal',
        ]);
    }

    public function test_soal_yang_sudah_dipakai_tetap_ada_setelah_soft_delete(): void
    {
        $materi = Materi::factory()->create();
        $pretest = Pretest::factory()->create();

        $soal = Soal::factory()->untukMateri($materi)->create();
        DB::table('pretest_jawaban')->insert([
            'pretest_id' => $pretest->id,
            'soal_id' => $soal->id,
            'urutan' => 1,
            'bobot' => 1,
        ]);

        $soal->delete();

        $this->assertSoftDeleted('soal', ['id' => $soal->id]);
        $this->assertDatabaseHas('pretest_jawaban', ['soal_id' => $soal->id]);
        $this->assertNull(Soal::find($soal->id));
    }

    public function test_progress_default_status_adalah_belajar(): void
    {
        $progress = ProgressBelajar::factory()->create([
            'status' => StatusProgress::Belajar,
        ]);

        $this->assertSame(StatusProgress::Belajar, $progress->fresh()->status);
    }

    public function test_simulasi_default_tidak_aktif(): void
    {
        $simulasi = Simulasi::factory()->tidakAktif()->create();

        $this->assertFalse($simulasi->fresh()->is_aktif);
    }
}
