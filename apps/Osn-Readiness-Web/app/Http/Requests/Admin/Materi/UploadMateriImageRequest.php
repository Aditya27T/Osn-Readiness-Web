<?php

namespace App\Http\Requests\Admin\Materi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadMateriImageRequest extends FormRequest
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
            'gambar' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }
}
