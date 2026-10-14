<?php

namespace App\Http\Requests;

use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Enums\TipeSoal;
use App\Models\KonteksSoal;
use App\Models\Materi;
use App\Support\AturanSoal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_sumber' => ['nullable', 'string', 'max:100'],
            'tingkat_id' => ['required', 'integer', 'exists:tingkat_seleksi,id'],
            'materi_id' => ['required', 'integer', 'exists:materi,id'],
            'konteks_id' => ['nullable', 'integer', 'exists:konteks_soal,id'],
            'level' => ['required', Rule::enum(Level::class)],
            'peruntukan' => ['required', Rule::enum(Peruntukan::class)],
            'tipe_soal' => ['required', Rule::enum(TipeSoal::class)],
            'pertanyaan' => ['required', 'string'],
            // Wajib-tidaknya pilihan bergantung pada tipe soal; diperiksa
            // AturanSoal di after().
            'pilihan_jawaban' => ['nullable', 'array'],
            'pilihan_jawaban.*' => ['required', 'string'],
            'kunci_jawaban' => ['required', 'string', 'max:255'],
            'gambar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $soal = $validator->validated();

                $galat = AturanSoal::periksa(
                    $soal,
                    Materi::find($soal['materi_id']),
                    isset($soal['konteks_id']) ? KonteksSoal::find($soal['konteks_id']) : null,
                    pilihanDikirim: true,
                );

                foreach ($galat as $kolom => $pesan) {
                    $validator->errors()->add($kolom, $pesan);
                }
            },
        ];
    }
}
