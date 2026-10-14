<?php

namespace App\Services;

use App\Contracts\Repositories\PretestRepositoryInterface;
use App\Contracts\Repositories\ProgressBelajarRepositoryInterface;
use App\Contracts\Repositories\QuizPengerjaanRepositoryInterface;
use App\Contracts\Repositories\RekomendasiMateriRepositoryInterface;
use App\Contracts\Services\AturanServiceInterface;
use App\Contracts\Services\SyaratSimulasiServiceInterface;
use App\DTOs\SyaratSimulasi;
use App\Enums\StatusProgress;
use App\Models\TingkatSeleksi;
use App\Models\User;

/**
 * Periksa materi wajib putaran aktif: progress harus berstatus selesai dan
 * nilai latihan terbaik mencapai latihan_min_nilai.
 *
 * Dipanggil dua kali: untuk endpoint status (tampilan) dan lagi di dalam
 * mulai simulasi sebagai penjaga yang sebenarnya (BE-08).
 */
class SyaratSimulasiService implements SyaratSimulasiServiceInterface
{
    public function __construct(
        private readonly PretestRepositoryInterface $pretestRepository,
        private readonly RekomendasiMateriRepositoryInterface $rekomendasiRepository,
        private readonly ProgressBelajarRepositoryInterface $progressRepository,
        private readonly QuizPengerjaanRepositoryInterface $quizPengerjaanRepository,
        private readonly AturanServiceInterface $aturanService,
    ) {}

    public function periksa(User $user, TingkatSeleksi $tingkat): SyaratSimulasi
    {
        // Putaran aktif diambil langsung dari pretest, bukan lewat
        // PutaranService: PutaranService sendiri bergantung pada service ini,
        // jadi saling menyuntik keduanya berujung rekursi tanpa henti.
        $putaranAktif = $this->pretestRepository->putaranAktifTerbaru($user->id, (int) $tingkat->id);

        // Tanpa putaran aktif belum ada materi wajib yang bisa diperiksa:
        // syarat belum terpenuhi dengan alasan belum pre-test.
        if ($putaranAktif === null) {
            return new SyaratSimulasi(terpenuhi: false, rincian: [], alasan: SyaratSimulasi::ALASAN_BELUM_PRETEST);
        }

        $aturan = $this->aturanService->untukTingkat((int) $tingkat->id);
        $rekomendasi = $this->rekomendasiRepository->untukPretest((int) $putaranAktif->id);
        $progress = $this->progressRepository->untukUserDiTingkat($user, (int) $tingkat->id);

        // Nilai terbaik semua latihan materi wajib diambil dalam satu query.
        $nilaiPerQuiz = $this->quizPengerjaanRepository->nilaiTerbaikPerQuiz(
            $user,
            $rekomendasi->map(fn ($baris): ?int => $baris->materi?->quiz?->id)->filter()->values()->all(),
        );

        $rincian = [];
        $terpenuhiSemua = true;

        foreach ($rekomendasi as $baris) {
            $materi = $baris->materi;
            $quiz = $materi?->quiz;

            $progressMateri = $progress->get((int) $baris->materi_id);
            $selesai = $progressMateri !== null
                && $progressMateri->status === StatusProgress::Selesai;

            // Nilai latihan terbaik = maksimum pengerjaan selesai.
            $nilaiTerbaik = $quiz === null ? null : ($nilaiPerQuiz[(int) $quiz->id] ?? null);

            $batas = (float) $aturan->latihanMinNilai;
            $latihanBelumTersedia = $quiz === null;
            $lolos = $selesai
                && ! $latihanBelumTersedia
                && $nilaiTerbaik !== null
                && $nilaiTerbaik >= $batas;

            if (! $lolos) {
                $terpenuhiSemua = false;
            }

            $rincian[] = [
                'materi_id' => (int) $baris->materi_id,
                'judul' => (string) $materi?->judul,
                'prioritas' => (int) $baris->prioritas,
                'selesai' => $selesai,
                'nilai_latihan' => $nilaiTerbaik === null ? null : (float) $nilaiTerbaik,
                'batas' => $batas,
                'latihan_belum_tersedia' => $latihanBelumTersedia,
            ];
        }

        return new SyaratSimulasi(
            terpenuhi: $terpenuhiSemua && $rincian !== [],
            rincian: $rincian,
        );
    }
}
