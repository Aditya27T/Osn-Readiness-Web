<?php

namespace App\Http\Resources;

use App\Models\Soal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Soal untuk dipakai siswa.
 *
 * Kunci jawaban tidak pernah ada di sini; hanya SoalReviewResource yang
 * memuatnya, dan itu pun setelah pengerjaan selesai.
 */
class SoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Soal $soal */
        $soal = $this->resource;

        return [
            'id' => $soal->id,
            'materi_id' => $soal->materi_id,
            'level' => $soal->level->value,
            'tipe_soal' => $soal->tipe_soal->value,
            'pertanyaan' => $soal->pertanyaan,
            'pilihan_jawaban' => $soal->pilihan_jawaban,
            'gambar' => $this->alamatGambar($soal),
            // Selalu dikirim, null bila soal tidak punya cerita, supaya
            // frontend tidak perlu membedakan kunci yang absen dengan null.
            'konteks' => $soal->konteks === null ? null : [
                'id' => $soal->konteks->id,
                'judul' => $soal->konteks->judul,
                'isi_konteks' => $soal->konteks->isi_konteks,
                'gambar' => $soal->konteks->gambar === null
                    ? null
                    : Storage::disk('public')->url($soal->konteks->gambar),
            ],
        ];
    }

    /**
     * Menyimpan nama file, bukan URL, supaya tetap benar bila domain berganti.
     */
    private function alamatGambar(Soal $soal): ?string
    {
        return $soal->gambar === null
            ? null
            : Storage::disk('public')->url($soal->gambar);
    }
}
