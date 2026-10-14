<?php

namespace App\Models;

use Database\Factories\PretestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('pretest')]
#[Fillable(['user_id', 'tingkat_id', 'disubmit_pada', 'nilai', 'selesai_pada'])]
#[UseFactory(PretestFactory::class)]
class Pretest extends Model
{
    /** @use HasFactory<PretestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'disubmit_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'nilai' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<TingkatSeleksi, $this>
     */
    public function tingkat(): BelongsTo
    {
        return $this->belongsTo(TingkatSeleksi::class);
    }

    /**
     * @return HasMany<PretestJawaban, $this>
     */
    public function jawaban(): HasMany
    {
        return $this->hasMany(PretestJawaban::class);
    }

    /**
     * @return HasMany<PemetaanMateri, $this>
     */
    public function pemetaanMateri(): HasMany
    {
        return $this->hasMany(PemetaanMateri::class);
    }

    /**
     * @return HasMany<RekomendasiMateri, $this>
     */
    public function rekomendasiMateri(): HasMany
    {
        return $this->hasMany(RekomendasiMateri::class);
    }

    /**
     * @return HasMany<HasilSimulasi, $this>
     */
    public function hasilSimulasi(): HasMany
    {
        return $this->hasMany(HasilSimulasi::class);
    }
}
