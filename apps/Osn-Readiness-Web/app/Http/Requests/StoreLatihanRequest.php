<?php

namespace App\Http\Requests;

use App\Contracts\Services\AturanServiceInterface;
use App\Models\Materi;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLatihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'materi_id.unique' => 'Materi ini sudah punya latihan; satu materi hanya boleh satu latihan.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'materi_id' => ['required', 'integer', 'exists:materi,id', 'unique:quiz,materi_id'],
            'nama_quiz' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'jumlah_soal' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * jumlah_soal minimal latihan_min_soal milik tingkat materinya (BE-18).
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

                $materi = Materi::find($this->integer('materi_id'));
                if (! $materi instanceof Materi) {
                    return;
                }

                $minimal = app(AturanServiceInterface::class)->untukTingkat((int) $materi->tingkat_id)->latihanMinSoal;

                if ((int) $this->input('jumlah_soal') < $minimal) {
                    $validator->errors()->add('jumlah_soal', "Jumlah soal latihan minimal {$minimal}.");
                }
            },
        ];
    }
}
