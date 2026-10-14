<?php

namespace App\Http\Requests\Simulasi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    /**
     * Tanpa tingkat_id, daftar memakai tingkat aktif siswa.
     */
    protected function prepareForValidation(): void
    {
        if ($this->query('tingkat_id') === null) {
            $this->merge(['tingkat_id' => $this->user()?->tingkat_aktif_id]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tingkat_id.required' => 'Tingkat belum diketahui: kirim tingkat_id atau selesaikan pre-test dulu.',
            'tingkat_id.integer' => 'Tingkat tidak dikenal.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tingkat_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
