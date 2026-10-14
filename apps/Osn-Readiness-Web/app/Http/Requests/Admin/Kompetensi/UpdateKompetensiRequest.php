<?php

namespace App\Http\Requests\Admin\Kompetensi;

use App\Models\Kompetensi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKompetensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_kompetensi.unique' => 'Nama kompetensi ini sudah ada di tingkat yang sama.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Kompetensi $kompetensi */
        $kompetensi = $this->route('kompetensi');

        return [
            'tingkat_id' => ['sometimes', 'integer', 'exists:tingkat_seleksi,id'],
            'nama_kompetensi' => [
                'sometimes', 'string', 'max:150',
                Rule::unique('kompetensi', 'nama_kompetensi')
                    ->where('tingkat_id', $this->input('tingkat_id', $kompetensi->tingkat_id))
                    ->ignore($kompetensi->id),
            ],
            'deskripsi' => ['sometimes', 'string'],
        ];
    }
}
