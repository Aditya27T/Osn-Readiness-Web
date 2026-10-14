<?php

namespace App\Http\Requests\Admin\Materi;

use App\Models\Kompetensi;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMateriRequest extends FormRequest
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
            'urutan.unique' => 'Urutan ini sudah dipakai materi lain di tingkat yang sama.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tingkat_id' => ['required', 'integer', 'exists:tingkat_seleksi,id'],
            'kompetensi_id' => ['required', 'integer', 'exists:kompetensi,id'],
            'urutan' => [
                'required', 'integer', 'min:1',
                Rule::unique('materi', 'urutan')->where('tingkat_id', $this->input('tingkat_id')),
            ],
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['required', 'string'],
            'isi_materi' => ['required', 'string'],
            'id_sumber' => ['nullable', 'string', 'max:100', 'unique:materi,id_sumber'],
            'file_materi' => ['nullable', 'url', 'max:255'],
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

                $kompetensi = Kompetensi::find($this->integer('kompetensi_id'));

                if ((int) $kompetensi?->tingkat_id !== $this->integer('tingkat_id')) {
                    $validator->errors()->add('kompetensi_id', 'Kompetensi harus berasal dari tingkat yang sama dengan materi.');
                }
            },
        ];
    }
}
