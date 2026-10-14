<?php

namespace App\Http\Requests\Admin\Materi;

use App\Models\Kompetensi;
use App\Models\Materi;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMateriRequest extends FormRequest
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
            'urutan.unique' => 'Urutan ini sudah dipakai materi lain di tingkat yang sama.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Materi $materi */
        $materi = $this->route('materi');
        $tingkatId = $this->input('tingkat_id', $materi->tingkat_id);

        return [
            'tingkat_id' => ['sometimes', 'integer', 'exists:tingkat_seleksi,id'],
            'kompetensi_id' => ['sometimes', 'integer', 'exists:kompetensi,id'],
            'urutan' => [
                'sometimes', 'integer', 'min:1',
                Rule::unique('materi', 'urutan')->where('tingkat_id', $tingkatId)->ignore($materi->id),
            ],
            'judul' => ['sometimes', 'string', 'max:200'],
            'deskripsi' => ['sometimes', 'string'],
            'isi_materi' => ['sometimes', 'string'],
            'id_sumber' => ['nullable', 'string', 'max:100', Rule::unique('materi', 'id_sumber')->ignore($materi->id)],
            'file_materi' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * Bila tingkat atau kompetensi diubah, keduanya diperiksa atas hasil
     * akhirnya, termasuk kolom yang tidak dikirim.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Materi $materi */
                $materi = $this->route('materi');

                $tingkatId = (int) $this->input('tingkat_id', $materi->tingkat_id);
                $kompetensi = Kompetensi::find((int) $this->input('kompetensi_id', $materi->kompetensi_id));

                // Hanya diperiksa bila tingkat atau kompetensi ikut dikirim,
                // supaya memperbaiki teks materi lama tidak ikut tertolak.
                if ($this->hasAny(['tingkat_id', 'kompetensi_id']) && (int) $kompetensi?->tingkat_id !== $tingkatId) {
                    $validator->errors()->add('kompetensi_id', 'Kompetensi harus berasal dari tingkat yang sama dengan materi.');
                }

                // Urutan lama bisa bentrok bila materi dipindah tingkat tanpa
                // mengirim urutan baru.
                if (! $this->has('urutan') && $tingkatId !== (int) $materi->tingkat_id
                    && Materi::query()->where('tingkat_id', $tingkatId)->where('urutan', $materi->urutan)->exists()) {
                    $validator->errors()->add('urutan', 'Urutan ini sudah dipakai materi lain di tingkat yang sama.');
                }
            },
        ];
    }
}
