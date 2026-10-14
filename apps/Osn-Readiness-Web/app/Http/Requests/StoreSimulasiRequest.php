<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSimulasiRequest extends FormRequest
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
            'nama_simulasi' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'jumlah_soal' => ['required', 'integer', 'min:1'],
            'durasi_menit' => ['required', 'integer', 'min:1'],
            'is_aktif' => ['required', 'boolean'],
        ];
    }
}
