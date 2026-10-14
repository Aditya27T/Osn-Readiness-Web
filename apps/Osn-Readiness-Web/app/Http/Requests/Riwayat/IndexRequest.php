<?php

namespace App\Http\Requests\Riwayat;

use App\Enums\JenisPengerjaan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.in' => 'Jenis hanya boleh pretest, latihan, atau simulasi.',
            'tingkat_id.integer' => 'Tingkat tidak dikenal.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis' => ['nullable', Rule::enum(JenisPengerjaan::class)],
            'tingkat_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
