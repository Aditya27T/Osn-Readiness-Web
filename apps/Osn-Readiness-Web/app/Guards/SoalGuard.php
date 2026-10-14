<?php

namespace App\Guards;

use App\Contracts\Repositories\SoalRepositoryInterface;
use App\Models\Soal;
use BackedEnum;
use Illuminate\Validation\ValidationException;

/**
 * Penjaga soal yang sudah dipakai pengerjaan siswa (BE-17, BE-21).
 *
 * Soal dipakai berarti barisnya dirujuk oleh jawaban pre-test, latihan, atau
 * simulasi. Sejak itu kunci, level, peruntukan, dan materinya tidak boleh
 * berubah, karena hasil yang sudah dinilai bergantung padanya. Teks
 * (pertanyaan, pilihan, cerita, gambar) tetap boleh diperbaiki.
 */
class SoalGuard
{
    /** @var list<string> */
    public const KOLOM_TERKUNCI = ['kunci_jawaban', 'level', 'peruntukan', 'materi_id'];

    public static function isInUse(Soal $soal): bool
    {
        return app(SoalRepositoryInterface::class)->sedangDipakai($soal);
    }

    /**
     * Kolom terkunci yang nilainya benar-benar berbeda dari soal saat ini.
     * Mengirim ulang nilai yang sama bukan perubahan.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public static function kolomTerkunciYangBerubah(Soal $soal, array $data): array
    {
        $berubah = [];

        foreach (self::KOLOM_TERKUNCI as $kolom) {
            if (! array_key_exists($kolom, $data)) {
                continue;
            }

            if (self::teks($data[$kolom]) !== self::teks($soal->getAttribute($kolom))) {
                $berubah[] = $kolom;
            }
        }

        return $berubah;
    }

    /**
     * Tolak perubahan kolom terkunci pada soal yang sudah dipakai.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public static function validateUpdate(Soal $soal, array $data): void
    {
        $berubah = self::kolomTerkunciYangBerubah($soal, $data);

        if ($berubah === [] || ! self::isInUse($soal)) {
            return;
        }

        $kolom = implode(', ', $berubah);

        throw ValidationException::withMessages([
            'soal' => "Tidak bisa mengubah {$kolom} pada soal yang sudah dipakai pengerjaan siswa.",
        ]);
    }

    private static function teks(mixed $nilai): string
    {
        return (string) ($nilai instanceof BackedEnum ? $nilai->value : $nilai);
    }
}
