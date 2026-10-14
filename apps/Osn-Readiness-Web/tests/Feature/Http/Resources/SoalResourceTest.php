<?php

namespace Tests\Feature\Http\Resources;

use App\Http\Resources\SoalResource;
use App\Http\Resources\SoalReviewResource;
use App\Models\KonteksSoal;
use App\Models\Materi;
use App\Models\Pembahasan;
use App\Models\PretestJawaban;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SoalResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function soal_resource_tidak_pernah_memuat_kunci_jawaban(): void
    {
        // Nilai kunci dibuat berbeda dari huruf pilihan supaya tidak salah
        // tertangkap sebagai teks biasa di dalam pilihan jawaban.
        $soal = Soal::factory()->pilihanGanda()->create(['kunci_jawaban' => 'RAHASIA']);

        $hasil = (new SoalResource($soal))->toArray(Request::create('/api/pretest'));

        $this->assertArrayNotHasKey('kunci_jawaban', $hasil);
        $this->assertStringNotContainsString('RAHASIA', json_encode($hasil) ?? '');
    }

    #[Test]
    public function soal_resource_memuat_field_yang_dibutuhkan_siswa(): void
    {
        $materi = Materi::factory()->create();
        $soal = Soal::factory()->untukMateri($materi)->create(['gambar' => 'soal/abc.png']);

        $hasil = (new SoalResource($soal))->toArray(Request::create('/api/pretest'));

        $this->assertSame($soal->id, $hasil['id']);
        $this->assertSame($materi->id, $hasil['materi_id']);
        $this->assertSame($soal->level->value, $hasil['level']);
        $this->assertSame($soal->tipe_soal->value, $hasil['tipe_soal']);
        $this->assertSame($soal->pertanyaan, $hasil['pertanyaan']);
        $this->assertSame($soal->pilihan_jawaban, $hasil['pilihan_jawaban']);
    }

    #[Test]
    public function soal_resource_mengirim_alamat_gambar_tanpa_nama_domain(): void
    {
        $soal = Soal::factory()->create(['gambar' => 'soal/abc.png']);

        $hasil = (new SoalResource($soal))->toArray(Request::create('/api/pretest'));

        $this->assertStringEndsWith('/storage/soal/abc.png', $hasil['gambar']);
    }

    #[Test]
    public function soal_tanpa_gambar_mengirim_null(): void
    {
        $soal = Soal::factory()->create(['gambar' => null]);

        $hasil = (new SoalResource($soal))->toArray(Request::create('/api/pretest'));

        $this->assertNull($hasil['gambar']);
    }

    #[Test]
    public function soal_dengan_konteks_menyertakan_isi_cerita(): void
    {
        $tingkat = TingkatSeleksi::factory()->create();
        $materi = Materi::factory()->create(['tingkat_id' => $tingkat->id]);
        $konteks = KonteksSoal::factory()->create([
            'tingkat_id' => $tingkat->id,
            'judul' => 'Cerita bersama',
            'isi_konteks' => 'Dikisahkan ada dua bilangan.',
            'gambar' => 'konteks/gambar.png',
        ]);

        $soal = Soal::factory()->untukMateri($materi)->create(['konteks_id' => $konteks->id]);
        $soal->load('konteks');

        $hasil = (new SoalResource($soal))->toArray(Request::create('/api/pretest'));

        $this->assertSame('Cerita bersama', $hasil['konteks']['judul']);
        $this->assertSame('Dikisahkan ada dua bilangan.', $hasil['konteks']['isi_konteks']);
        $this->assertStringEndsWith('/storage/konteks/gambar.png', $hasil['konteks']['gambar']);
    }

    #[Test]
    public function soal_tanpa_konteks_mengirim_null(): void
    {
        $soal = Soal::factory()->create(['konteks_id' => null]);
        $soal->load('konteks');

        $hasil = (new SoalResource($soal))->toArray(Request::create('/api/pretest'));

        $this->assertArrayHasKey('konteks', $hasil);
        $this->assertNull($hasil['konteks']);
    }

    #[Test]
    public function soal_review_memuat_kunci_dan_pembahasan(): void
    {
        $materi = Materi::factory()->create();
        $soal = Soal::factory()->untukMateri($materi)->create(['kunci_jawaban' => 'C']);
        Pembahasan::factory()->create([
            'soal_id' => $soal->id,
            'isi_pembahasan' => 'Pembahasan contoh.',
        ]);

        $jawaban = PretestJawaban::factory()->create([
            'soal_id' => $soal->id,
            'urutan' => 3,
            'bobot' => 2,
            'jawaban_user' => 'A',
            'status_benar' => false,
        ]);
        $jawaban->load('soal.konteks', 'soal.pembahasan');

        $hasil = (new SoalReviewResource($jawaban))->toArray(Request::create('/api/pretest'));

        $this->assertSame(3, $hasil['urutan']);
        $this->assertSame(2, $hasil['bobot']);
        $this->assertSame('A', $hasil['jawaban_user']);
        $this->assertFalse($hasil['status_benar']);
        $this->assertSame('C', $hasil['soal']['kunci_jawaban']);
        $this->assertSame('Pembahasan contoh.', $hasil['soal']['pembahasan']);
        $this->assertNull($hasil['soal']['konteks']);
    }

    #[Test]
    public function model_soal_menyembunyikan_kunci_jawaban_secara_default(): void
    {
        $soal = Soal::factory()->create(['kunci_jawaban' => 'C']);

        $this->assertArrayNotHasKey('kunci_jawaban', $soal->toArray());
    }
}
