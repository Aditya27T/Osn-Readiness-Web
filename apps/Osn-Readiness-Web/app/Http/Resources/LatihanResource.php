<?php

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Latihan (Quiz) resource untuk admin view.
 */
class LatihanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Quiz $quiz */
        $quiz = $this->resource;

        return [
            'id' => $quiz->id,
            'materi_id' => $quiz->materi_id,
            'nama_quiz' => $quiz->nama_quiz,
            'deskripsi' => $quiz->deskripsi,
            'jumlah_soal' => $quiz->jumlah_soal,
            'materi' => $this->whenLoaded('materi', fn () => [
                'id' => $quiz->materi->id,
                'judul' => $quiz->materi->judul,
            ]),
            'created_at' => $quiz->created_at,
            'updated_at' => $quiz->updated_at,
        ];
    }
}
