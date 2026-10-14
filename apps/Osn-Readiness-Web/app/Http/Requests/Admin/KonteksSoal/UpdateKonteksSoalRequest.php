<?php

namespace App\Http\Requests\Admin\KonteksSoal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKonteksSoalRequest extends FormRequest
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
            'tingkat_id' => ['sometimes', 'integer', 'exists:tingkat_seleksi,id'],
            'judul' => ['sometimes', 'string', 'max:200'],
            'isi_konteks' => ['sometimes', 'string'],
            'gambar' => ['nullable', 'string'],
        ];
    }
}
