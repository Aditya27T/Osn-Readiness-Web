<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Pembagian kuota soal per level kesulitan.
 *
 * Jumlah soal dikali persen tiap level, lalu dibulatkan ke bawah; sisa soal
 * diberikan ke level dengan pecahan terbesar. Untuk 30 soal dengan persen
 * 50/30/20 hasilnya 15, 9, 6 tanpa sisa.
 *
 * Dipakai SoalPickerService dan BankSoalService supaya laporan admin dan
 * perilaku nyata tidak pernah berbeda.
 */
final class KuotaLevel
{
    /**
     * @param  array<int, float>  $persen  diindeks 0, 1, 2 untuk mudah, sedang, sulit
     * @return array<int, int> kuota per indeks level, jumlahnya sama dengan $jumlah
     */
    public static function hitung(int $jumlah, array $persen): array
    {
        if ($jumlah < 0) {
            throw new InvalidArgumentException('Jumlah soal tidak boleh negatif.');
        }

        if ($persen === []) {
            throw new InvalidArgumentException('Persen level tidak boleh kosong.');
        }

        $kuota = [];
        $pecahan = [];

        foreach ($persen as $level => $persenLevel) {
            $exact = $jumlah * $persenLevel / 100;
            $kuota[$level] = (int) floor($exact);
            $pecahan[$level] = $exact - $kuota[$level];
        }

        ksort($kuota);
        ksort($pecahan);

        $sisa = $jumlah - array_sum($kuota);

        if ($sisa > 0) {
            // Level dengan persen 0 tidak boleh ikut menerima sisa soal.
            // Persen 0 berarti level itu memang tidak dipakai, dan kalau ia
            // ikut pecah sisa, bank soal yang tidak punya soal level tersebut
            // akan selalu gagal dan tidak ada cara menyalakannya lewat kuota.
            $urut = array_values(array_filter(
                array_keys($kuota),
                fn (int $level): bool => $persen[$level] > 0,
            ));

            // Seluruh persen nol: tidak ada yang berhak, jadi sisa dibagi
            // rata ke semua level supaya jumlah tetap utuh.
            if ($urut === []) {
                $urut = array_keys($kuota);
            }

            // Pecahan terbesar didahulukan. Seri dipecah menurut indeks level
            // supaya hasilnya deterministik.
            usort($urut, function (int $a, int $b) use ($pecahan): int {
                if ($pecahan[$a] === $pecahan[$b]) {
                    return $a <=> $b;
                }

                return $pecahan[$b] <=> $pecahan[$a];
            });

            $posisi = 0;

            while ($sisa > 0) {
                $kuota[$urut[$posisi % count($urut)]]++;
                $sisa--;
                $posisi++;
            }
        }

        ksort($kuota);

        return $kuota;
    }
}
