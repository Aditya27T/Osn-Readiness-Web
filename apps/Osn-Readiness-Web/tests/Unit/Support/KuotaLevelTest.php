<?php

namespace Tests\Unit\Support;

use App\Support\KuotaLevel;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KuotaLevelTest extends TestCase
{
    #[Test]
    public function tiga_puluh_soal_membagi_menjadi_sembilan_belas_enam(): void
    {
        // Angka resmi di Logic per fitur: 30 soal menghasilkan 15, 9, 6.
        $this->assertSame([15, 9, 6], KuotaLevel::hitung(30, [50.0, 30.0, 20.0]));
    }

    #[Test]
    public function jumlah_kuota_selalu_sama_dengan_jumlah_soal(): void
    {
        foreach ([1, 7, 10, 23, 30, 31, 45, 100] as $jumlah) {
            $this->assertSame(
                $jumlah,
                array_sum(KuotaLevel::hitung($jumlah, [50.0, 30.0, 20.0])),
                "Jumlah kuota tidak cocok untuk {$jumlah} soal.",
            );
        }
    }

    #[Test]
    public function sisa_soal_diberikan_ke_level_dengan_pecahan_terbesar(): void
    {
        // 7 x 50% = 3,5 · 7 x 30% = 2,1 · 7 x 20% = 1,4.
        // Pecahan terbesar 0,5 ada di level mudah, jadi sisa satu soal ke sana.
        $this->assertSame([4, 2, 1], KuotaLevel::hitung(7, [50.0, 30.0, 20.0]));
    }

    #[Test]
    public function kuota_tidak_pernah_negatif_saat_persen_menurun(): void
    {
        $kuota = KuotaLevel::hitung(4, [70.0, 20.0, 10.0]);

        $this->assertSame([3, 1, 0], $kuota);
        $this->assertGreaterThanOrEqual(0, min($kuota));
    }

    #[Test]
    public function persen_simulasi_tiga_puluh_empat_puluh_tiga_puluh(): void
    {
        // Kuota simulasi dari AturanPemetaanSeeder.
        $this->assertSame([9, 12, 9], KuotaLevel::hitung(30, [30.0, 40.0, 30.0]));
    }

    #[Test]
    public function satu_soal_jatuh_ke_level_dengan_persen_terbesar(): void
    {
        // 1 x 50% = 0,5 · 1 x 30% = 0,3 · 1 x 20% = 0,2. Semua lantai nol,
        // jadi sisa satu soal jatuh ke level dengan pecahan terbesar.
        $this->assertSame([1, 0, 0], KuotaLevel::hitung(1, [50.0, 30.0, 20.0]));
    }

    #[Test]
    public function level_bersen_nol_tidak_ikut_menerima_sisa(): void
    {
        // Bank soal kabupaten tidak punya soal pretest berlevel sedang, jadi
        // kuota diturunkan ke 50/0/20. Sisa soal harus tetap jatuh ke level
        // yang persennya bukan nol, bukan ke sedang yang sudah dinolkan.
        $this->assertSame([20, 0, 10], KuotaLevel::hitung(30, [50.0, 0.0, 20.0]));
    }

    #[Test]
    public function semua_persen_nol_tetap_menjumlah_ke_jumlah_soal(): void
    {
        // Tidak ada level yang berhak, tapi jumlah tetap harus utuh supaya
        // kuota tidak pernah melebihi jumlah soal.
        $kuota = KuotaLevel::hitung(10, [0.0, 0.0, 0.0]);

        $this->assertSame(10, array_sum($kuota));
    }

    #[Test]
    public function jumlah_negatif_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        KuotaLevel::hitung(-1, [50.0, 30.0, 20.0]);
    }

    #[Test]
    public function persen_kosong_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        KuotaLevel::hitung(10, []);
    }

    #[Test]
    public function hasil_deterministik_untuk_input_yang_sama(): void
    {
        $pertama = KuotaLevel::hitung(17, [33.0, 33.0, 34.0]);
        $kedua = KuotaLevel::hitung(17, [33.0, 33.0, 34.0]);

        $this->assertSame($pertama, $kedua);
        $this->assertSame(17, array_sum($pertama));
    }
}
