<?php

namespace App\Models;

use Database\Factories\PretestJawabanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pretest_jawaban')]
#[Fillable(['pretest_id', 'soal_id', 'urutan', 'bobot', 'jawaban_user', 'status_benar'])]
#[UseFactory(PretestJawabanFactory::class)]
class PretestJawaban extends Model
{
    /** @use HasFactory<PretestJawabanFactory> */
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
     * @return BelongsTo<Pretest, $this>
     */
    public function pretest(): BelongsTo
    {
        return $this->belongsTo(Pretest::class);
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
