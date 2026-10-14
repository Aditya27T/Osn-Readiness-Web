<?php

namespace App\Http\Requests;

use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Enums\TipeSoal;
use App\Models\KonteksSoal;
use App\Models\Materi;
use App\Models\Soal;
use App\Support\AturanSoal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSoalRequest extends FormRequest
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
            'tingkat_id' => ['sometimes', 'required', 'integer', 'exists:tingkat_seleksi,id'],
            'materi_id' => ['sometimes', 'required', 'integer', 'exists:materi,id'],
            'konteks_id' => ['nullable', 'integer', 'exists:konteks_soal,id'],
            'level' => ['sometimes', 'required', Rule::enum(Level::class)],
            'peruntukan' => ['sometimes', 'required', Rule::enum(Peruntukan::class)],
            'tipe_soal' => ['sometimes', 'required', Rule::enum(TipeSoal::class)],
            'pertanyaan' => ['sometimes', 'required', 'string'],
            // Wajib-tidaknya pilihan bergantung pada tipe soal; diperiksa
            // AturanSoal di after().
            'pilihan_jawaban' => ['nullable', 'array'],
            'pilihan_jawaban.*' => ['required', 'string'],
            'kunci_jawaban' => ['sometimes', 'required', 'string', 'max:255'],
            'gambar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * Kolom yang tidak dikirim memakai nilai soal saat ini, supaya aturan
     * silang (tingkat, tipe, pilihan, kunci) diperiksa atas hasil akhirnya.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Soal $lama */
                $lama = $this->route('soal');
                $kiriman = $validator->validated();

                $soal = array_merge([
                    'tingkat_id' => $lama->tingkat_id,
                    'materi_id' => $lama->materi_id,
                    'konteks_id' => $lama->konteks_id,
                    'tipe_soal' => $lama->tipe_soal->value,
                    'pilihan_jawaban' => $lama->pilihan_jawaban,
                    'kunci_jawaban' => $lama->kunci_jawaban,
                ], $kiriman);

                // Berganti ke isian tanpa mengirim pilihan berarti pilihan
                // lama ikut dibuang; SoalAdminService yang mengosongkannya.
                if ($soal['tipe_soal'] === TipeSoal::Isian->value && ! array_key_exists('pilihan_jawaban', $kiriman)) {
                    $soal['pilihan_jawaban'] = null;
                }

                $galat = AturanSoal::periksa(
                    $soal,
                    Materi::find($soal['materi_id']),
                    $soal['konteks_id'] === null ? null : KonteksSoal::find($soal['konteks_id']),
                    pilihanDikirim: array_key_exists('pilihan_jawaban', $kiriman),
                );

                foreach ($galat as $kolom => $pesan) {
                    $validator->errors()->add($kolom, $pesan);
                }
            },
        ];
    }
}
