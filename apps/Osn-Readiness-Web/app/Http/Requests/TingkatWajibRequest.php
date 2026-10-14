<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Endpoint baca yang meminta satu tingkat lewat query ?tingkat_id=.
 */
class TingkatWajibRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tingkat_id.required' => 'tingkat_id wajib dikirim.',
            'tingkat_id.exists' => 'Tingkat tidak dikenal.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tingkat_id' => ['required', 'integer', 'exists:tingkat_seleksi,id'],
        ];
    }
}
