<?php

namespace App\Http\Requests\Admin\KonteksSoal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreKonteksSoalRequest extends FormRequest
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
            'tingkat_id' => ['required', 'integer', 'exists:tingkat_seleksi,id'],
            'judul' => ['required', 'string', 'max:200'],
            'isi_konteks' => ['required', 'string'],
            'gambar' => ['nullable', 'string'],
        ];
    }
}
