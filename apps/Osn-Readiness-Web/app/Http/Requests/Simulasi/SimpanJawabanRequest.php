<?php

namespace App\Http\Requests\Simulasi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SimpanJawabanRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'soal_id.required' => 'Soal yang dijawab wajib disebut.',
            'soal_id.integer' => 'Soal tidak dikenal.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'soal_id' => ['required', 'integer', 'min:1'],
            'jawaban_user' => ['nullable', 'string', 'max:255'],
        ];
    }
}
