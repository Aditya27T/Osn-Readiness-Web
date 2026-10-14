<?php

namespace App\Http\Resources;

use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu materi di layar belajar siswa: tanda wajib, prioritas, status
 * progress, dan nilai latihan terbaik.
 */
class MateriBelajarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $baris */
        $baris = $this->resource;

        return $baris;
    }
}
