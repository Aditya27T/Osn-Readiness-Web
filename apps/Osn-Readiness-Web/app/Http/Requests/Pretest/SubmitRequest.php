<?php

namespace App\Http\Requests\Pretest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitRequest extends FormRequest
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
        // Submit tidak membawa apa pun: seluruh jawaban sudah tersimpan
        // per soal. Endpoint ini hanya mengunci dan menilai.
        return [];
    }
}
