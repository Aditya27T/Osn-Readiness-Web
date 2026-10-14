<?php

namespace App\Http\Requests\Belajar;

use App\Enums\StatusProgress;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgressRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status progress wajib disebut.',
            'status.enum' => 'Status hanya boleh belajar atau selesai.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(StatusProgress::class)],
        ];
    }
}
