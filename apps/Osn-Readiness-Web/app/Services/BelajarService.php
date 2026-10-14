<?php

namespace App\Services;

use App\Contracts\Repositories\MateriRepositoryInterface;
use App\Contracts\Repositories\ProgressBelajarRepositoryInterface;
use App\Contracts\Repositories\QuizPengerjaanRepositoryInterface;
use App\Contracts\Repositories\RekomendasiMateriRepositoryInterface;
use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Services\BelajarServiceInterface;
use App\Contracts\Services\PutaranServiceInterface;
use App\DTOs\StatusPutaran;
use App\Enums\StatusProgress;
use App\Exceptions\BelumPretestException;
use App\Exceptions\TingkatTerkunciException;
use App\Models\Materi;
use App\Models\ProgressBelajar;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class BelajarService implements BelajarServiceInterface
{
    public function __construct(
        private readonly MateriRepositoryInterface $materiRepository,
        private readonly ProgressBelajarRepositoryInterface $progressRepository,
        private readonly RekomendasiMateriRepositoryInterface $rekomendasiRepository,
        private readonly QuizPengerjaanRepositoryInterface $quizPengerjaanRepository,
        private readonly TingkatSeleksiRepositoryInterface $tingkatRepository,
        private readonly PutaranServiceInterface $putaranService,
    ) {}

    public function daftar(User $user, int $tingkatId): Collection
    {
        $status = $this->pastikanTerbuka($user, $tingkatId);

        $materi = $this->materiRepository->untukTingkat($tingkatId);

        if ($materi->isEmpty()) {
            return new Collection;
        }

        $tanda = $this->tandaMateriWajib($status);
        $progress = $this->progressRepository->untukUserDiTingkat($user, $tingkatId);
        $nilaiTerbaik = $this->nilaiLatihanTerbaik($user, $materi);

        $baris = $materi->map(fn (Materi $satu): array => $this->butir(
            materi: $satu,
            wajib: $tanda['wajib'][(int) $satu->id] ?? false,
            prioritas: $tanda['prioritas'][(int) $satu->id] ?? null,
            progress: $progress->get((int) $satu->id),
            nilaiTerbaik: $nilaiTerbaik[(int) $satu->id] ?? null,
        ));

        // Materi wajib ditampilkan lebih dulu, diurutkan prioritas lalu
        // urutan materi.
        return $baris->sort(function (array $a, array $b): int {
            if ($a['wajib'] !== $b['wajib']) {
                return $a['wajib'] ? -1 : 1;
            }

            if ($a['wajib']) {
                return ($a['prioritas'] ?? PHP_INT_MAX) <=> ($b['prioritas'] ?? PHP_INT_MAX);
            }

            return $a['urutan'] <=> $b['urutan'];
        })->values();
    }

    public function detail(User $user, Materi $materi): array
    {
        $status = $this->pastikanTerbuka($user, (int) $materi->tingkat_id);

        $tanda = $this->tandaMateriWajib($status);
        $progress = $this->progressRepository->find($user, (int) $materi->id);
        $nilaiTerbaik = $this->nilaiLatihanTerbaik($user, new Collection([$materi]));

        return $this->butir(
            materi: $materi,
            wajib: $tanda['wajib'][(int) $materi->id] ?? false,
            prioritas: $tanda['prioritas'][(int) $materi->id] ?? null,
            progress: $progress,
            nilaiTerbaik: $nilaiTerbaik[(int) $materi->id] ?? null,
            isi: true,
        );
    }

    public function perbaruiProgress(User $user, int $materiId, string $status): ProgressBelajar
    {
        $materi = $this->materiRepository->findOrFail($materiId);
        $tingkat = $materi->tingkat;

        $statusPutaran = $this->putaranService->status($user, $tingkat);

        if (! $statusPutaran->tingkatTerbuka) {
            throw new TingkatTerkunciException("Tingkat {$tingkat->nama_tingkat} belum terbuka.");
        }

        // Menandai selesai hanya dicatat saat ada putaran aktif; tanpa itu
        // siswa bisa memenuhi syarat simulasi sebelum pre-test baru dipetakan.
        if ($statusPutaran->putaranAktifId === null) {
            throw new BelumPretestException('Tandai materi selesai setelah pre-test.');
        }

        return $this->progressRepository->simpan($user, $materiId, [
            'status' => $status,
            'persentase' => $status === StatusProgress::Selesai->value ? 100 : 0,
            'tanggal_selesai' => $status === StatusProgress::Selesai->value ? now() : null,
        ]);
    }

    private function pastikanTerbuka(User $user, int $tingkatId): StatusPutaran
    {
        $tingkat = $this->tingkatRepository->find($tingkatId);

        if (! $tingkat instanceof TingkatSeleksi) {
            throw (new ModelNotFoundException)->setModel(TingkatSeleksi::class, [$tingkatId]);
        }

        $status = $this->putaranService->status($user, $tingkat);

        if (! $status->tingkatTerbuka) {
            throw new TingkatTerkunciException("Tingkat {$tingkat->nama_tingkat} belum terbuka.");
        }

        return $status;
    }

    /**
     * Tanda wajib dan prioritas diambil dari rekomendasi_materi putaran
     * aktif; tanpa putaran aktif tidak ada materi yang wajib.
     *
     * @return array{wajib: array<int, bool>, prioritas: array<int, int>}
     */
    private function tandaMateriWajib(StatusPutaran $statusPutaran): array
    {
        if ($statusPutaran->putaranAktifId === null) {
            return ['wajib' => [], 'prioritas' => []];
        }

        $rekomendasi = $this->rekomendasiRepository->untukPretest($statusPutaran->putaranAktifId);

        $wajib = [];
        $prioritas = [];

        foreach ($rekomendasi as $baris) {
            $wajib[(int) $baris->materi_id] = true;
            $prioritas[(int) $baris->materi_id] = (int) $baris->prioritas;
        }

        return ['wajib' => $wajib, 'prioritas' => $prioritas];
    }

    /**
     * Nilai latihan sebuah materi adalah nilai tertinggi dari semua
     * pengerjaan quiz materi itu yang sudah selesai.
     *
     * @param  Collection<int, Materi>  $materi
     * @return array<int, float>
     */
    private function nilaiLatihanTerbaik(User $user, Collection $materi): array
    {
        // Latihan dan nilai terbaiknya diambil sekaligus, bukan per materi.
        (new EloquentCollection($materi->all()))->loadMissing('quiz');

        $nilaiPerQuiz = $this->quizPengerjaanRepository->nilaiTerbaikPerQuiz(
            $user,
            $materi->map(fn (Materi $satu): ?int => $satu->quiz?->id)->filter()->values()->all(),
        );

        $nilai = [];

        foreach ($materi as $satu) {
            $quizId = $satu->quiz?->id;

            if ($quizId !== null && isset($nilaiPerQuiz[(int) $quizId])) {
                $nilai[(int) $satu->id] = $nilaiPerQuiz[(int) $quizId];
            }
        }

        return $nilai;
    }

    /**
     * @return array<string, mixed>
     */
    private function butir(
        Materi $materi,
        bool $wajib,
        ?int $prioritas,
        ?ProgressBelajar $progress,
        ?float $nilaiTerbaik,
        bool $isi = false,
    ): array {
        $baris = [
            'id' => (int) $materi->id,
            'tingkat_id' => (int) $materi->tingkat_id,
            'kompetensi_id' => (int) $materi->kompetensi_id,
            'urutan' => (int) $materi->urutan,
            'judul' => $materi->judul,
            'deskripsi' => $materi->deskripsi,
            'wajib' => $wajib,
            'prioritas' => $prioritas,
            'progress' => $progress === null ? null : [
                'status' => $progress->status->value,
                'persentase' => (int) $progress->persentase,
                'tanggal_selesai' => $progress->tanggal_selesai?->toISOString(),
            ],
            'nilai_latihan_terbaik' => $nilaiTerbaik,
            'quiz_id' => $materi->quiz === null ? null : (int) $materi->quiz->id,
            'latihan_belum_tersedia' => $materi->quiz === null,
        ];

        if ($isi) {
            $baris['isi_materi'] = $materi->isi_materi;
            $baris['file_materi'] = $materi->file_materi;
            $baris['gambar'] = $materi->gambar;
        }

        return $baris;
    }
}
