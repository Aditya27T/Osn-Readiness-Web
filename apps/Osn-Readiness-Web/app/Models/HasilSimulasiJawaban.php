<?php

namespace App\Models;

use Database\Factories\HasilSimulasiJawabanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('hasil_simulasi_jawaban')]
#[Fillable(['hasil_simulasi_id', 'soal_id', 'urutan', 'bobot', 'jawaban_user', 'status_benar'])]
#[UseFactory(HasilSimulasiJawabanFactory::class)]
class HasilSimulasiJawaban extends Model
{
    /** @use HasFactory<HasilSimulasiJawabanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_benar' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<HasilSimulasi, $this>
     */
    public function hasilSimulasi(): BelongsTo
    {
        return $this->belongsTo(HasilSimulasi::class);
    }

    /**
     * @return BelongsTo<Soal, $this>
     */
    public function soal(): BelongsTo
    {
        // Soal yang di-soft delete admin tetap harus bisa dinilai dan
        // ditinjau oleh pengerjaan yang sudah memuatnya.
        return $this->belongsTo(Soal::class)->withTrashed();
    }
}
