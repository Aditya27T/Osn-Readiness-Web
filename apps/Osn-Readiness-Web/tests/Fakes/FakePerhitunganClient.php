<?php

namespace Tests\Fakes;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Exceptions\PerhitunganTidakTersediaException;
use Closure;
use Throwable;

/**
 * PerhitunganClient palsu untuk test dan pengembangan lokal tanpa layanan
 * Python. Paket berikutnya memakai ini supaya tidak perlu HTTP sama sekali.
 */
class FakePerhitunganClient implements PerhitunganClientInterface
{
    /** @var list<array<string, mixed>> */
    public array $permintaanPenilaian = [];

    /** @var list<array<string, mixed>> */
    public array $permintaanPretest = [];

    public float $nilai = 75.0;

    /** @var int|null bila diisi, panggilan berikutnya melempar 503 */
    public ?int $gagalDengan = null;

    /**
     * Dijalankan tiap kali layanan hitung dipanggil. Test memakainya untuk
     * meniru penilai lain (job atau submit ulang) yang selesai lebih dulu
     * saat panggilan ini masih berjalan.
     */
    public ?Closure $saatDipanggil = null;

    /** Bila diisi, panggilan berikutnya melempar exception ini apa adanya. */
    public ?Throwable $lempar = null;

    public function hitungPenilaian(array $soal): array
    {
        $this->permintaanPenilaian[] = ['soal' => $soal];

        $this->mungkinGagal();

        return [
            'nilai' => $this->nilai,
            'jawaban' => $this->statusUntuk(array_column($soal, 'soal_id')),
        ];
    }

    public function hitungPretest(array $soal, array $materi, int $jumlahMateriWajib): array
    {
        $this->permintaanPretest[] = [
            'soal' => $soal,
            'materi' => $materi,
            'jumlah_materi_wajib' => $jumlahMateriWajib,
        ];

        $this->mungkinGagal();

        $perMateri = array_count_values(array_column($soal, 'materi_id'));
        $pemetaan = [];
        $peringkat = 1;

        foreach ($materi as $baris) {
            $materiId = (int) $baris['materi_id'];
            $jumlahSoal = (int) ($perMateri[$materiId] ?? 0);
            $poin = 0;

            foreach (array_filter($soal, fn (array $s): bool => (int) $s['materi_id'] === $materiId) as $s) {
                $poin += (int) $s['bobot'];
            }

            $pemetaan[] = [
                'materi_id' => $materiId,
                'jumlah_soal' => $jumlahSoal,
                'jumlah_benar' => $jumlahSoal,
                'poin_didapat' => $poin,
                'poin_maksimal' => $poin,
                'persentase' => 100.0,
                'peringkat' => $peringkat,
            ];

            $peringkat++;
        }

        $materiWajib = [];

        foreach (array_slice($materi, 0, $jumlahMateriWajib) as $index => $baris) {
            $materiWajib[] = [
                'materi_id' => (int) $baris['materi_id'],
                'prioritas' => $index + 1,
            ];
        }

        return [
            'nilai' => $this->nilai,
            'jawaban' => $this->statusUntuk(array_column($soal, 'soal_id')),
            'pemetaan' => $pemetaan,
            'materi_wajib' => $materiWajib,
        ];
    }

    /**
     * @param  array<int, int>  $soalIds
     * @return list<array{soal_id: int, status_benar: bool}>
     */
    private function statusUntuk(array $soalIds): array
    {
        $daftar = [];

        foreach ($soalIds as $soalId) {
            $daftar[] = ['soal_id' => (int) $soalId, 'status_benar' => true];
        }

        return $daftar;
    }

    private function mungkinGagal(): void
    {
        if ($this->saatDipanggil !== null) {
            ($this->saatDipanggil)();
        }

        if ($this->lempar !== null) {
            throw $this->lempar;
        }

        if ($this->gagalDengan !== null) {
            throw new PerhitunganTidakTersediaException("Fake gagal dengan {$this->gagalDengan}.");
        }
    }
}
