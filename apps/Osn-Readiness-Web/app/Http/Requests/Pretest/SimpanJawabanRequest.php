<?php

namespace App\Http\Requests\Pretest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SimpanJawabanRequest extends FormRequest
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
            'soal_id' => ['required', 'integer'],
            // Isian boleh kosong, jadi nullable dan bukan required.
            'jawaban_user' => ['nullable', 'string', 'max:255'],
        ];
    }
}
