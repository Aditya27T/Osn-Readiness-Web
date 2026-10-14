<?php

namespace App\Http\Requests\Admin\Tingkat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTingkatRequest extends FormRequest
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
            // Hanya nama dan deskripsi; urutan menentukan tingkat mana yang
            // terbuka lebih dulu dan tidak diubah lewat admin (BE-13).
            'nama_tingkat' => ['sometimes', 'string', 'max:100'],
            'deskripsi' => ['sometimes', 'string'],
        ];
    }
}
