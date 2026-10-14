<?php

namespace Tests\Feature\Database;

use App\Enums\JenisPengerjaan;
use App\Models\HasilSimulasi;
use App\Models\Materi;
use App\Models\Pretest;
use App\Models\Quiz;
use App\Models\QuizPengerjaan;
use App\Models\RiwayatHasil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiwayatHasilTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_menggabungkan_ketiga_jenis_pengerjaan(): void
    {
        $user = User::factory()->create();
        $materi = Materi::factory()->create();

        Pretest::factory()->selesai(70)->create(['user_id' => $user->id]);

        QuizPengerjaan::factory()->selesai(80)->create([
            'user_id' => $user->id,
            'quiz_id' => Quiz::factory()->for($materi)->create()->id,
        ]);

        $pretest = Pretest::factory()->selesai()->create(['user_id' => $user->id]);
        HasilSimulasi::factory()->selesai(90)->create([
            'user_id' => $user->id,
            'pretest_id' => $pretest->id,
        ]);

        $jumlah = RiwayatHasil::where('user_id', $user->id)->count();

        $this->assertSame(4, $jumlah);

        $jenis = RiwayatHasil::where('user_id', $user->id)
            ->distinct()
            ->orderBy('jenis_hasil')
            ->pluck('jenis_hasil')
            ->map(fn (mixed $nilai): string => $nilai instanceof JenisPengerjaan ? $nilai->value : $nilai)
            ->all();

        $this->assertSame(
            ['latihan', 'pretest', 'simulasi'],
            $jenis,
        );
    }

    public function test_pengerjaan_yang_belum_selesai_tidak_ikut_muncul(): void
    {
        $user = User::factory()->create();

        Pretest::factory()->berjalan()->create(['user_id' => $user->id]);
        Pretest::factory()->terkunci()->create(['user_id' => $user->id]);

        $this->assertSame(0, RiwayatHasil::where('user_id', $user->id)->count());
    }

    public function test_tingkat_latihan_diambil_dari_materi(): void
    {
        $user = User::factory()->create();
        $materi = Materi::factory()->create();

        QuizPengerjaan::factory()->selesai()->create([
            'user_id' => $user->id,
            'quiz_id' => Quiz::factory()->for($materi)->create()->id,
        ]);

        $riwayat = RiwayatHasil::where('user_id', $user->id)
            ->where('jenis_hasil', JenisPengerjaan::Latihan->value)
            ->firstOrFail();

        $this->assertSame($materi->tingkat_id, (int) $riwayat->tingkat_id);
    }

    public function test_model_tidak_pakai_timestamps(): void
    {
        $this->assertFalse((new RiwayatHasil)->usesTimestamps());
    }
}
