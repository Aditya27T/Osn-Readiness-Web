<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Seluruh 16 parameter wajib dikirim utuh: admin mengedit satu layar aturan
 * per tingkat, bukan parameter tunggal. Aturan lintas-batas (jumlah persen,
 * kecukupan soal, materi wajib) diperiksa di AturanPemetaanService.
 *
 * @return array<string, array<int, mixed>>
 */
class UpdateAturanPemetaanRequest extends FormRequest
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
            'bobot_mudah' => ['required', 'integer', 'min:1', 'max:100'],
            'bobot_sedang' => ['required', 'integer', 'min:1', 'max:100'],
            'bobot_sulit' => ['required', 'integer', 'min:1', 'max:100'],
            'pretest_jumlah_soal' => ['required', 'integer', 'min:1', 'max:200'],
            'pretest_persen_mudah' => ['required', 'integer', 'min:0', 'max:100'],
            'pretest_persen_sedang' => ['required', 'integer', 'min:0', 'max:100'],
            'pretest_persen_sulit' => ['required', 'integer', 'min:0', 'max:100'],
            'pretest_min_soal_per_materi' => ['required', 'integer', 'min:1'],
            'jumlah_materi_wajib' => ['required', 'integer', 'min:1'],
            'latihan_min_soal' => ['required', 'integer', 'min:1'],
            'latihan_min_nilai' => ['required', 'integer', 'min:0', 'max:100'],
            'simulasi_persen_mudah' => ['required', 'integer', 'min:0', 'max:100'],
            'simulasi_persen_sedang' => ['required', 'integer', 'min:0', 'max:100'],
            'simulasi_persen_sulit' => ['required', 'integer', 'min:0', 'max:100'],
            'simulasi_maks_percobaan' => ['required', 'integer', 'min:1'],
            'passing_grade' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
