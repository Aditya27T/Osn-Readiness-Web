<?php

namespace App\Models;

use Database\Factories\RekomendasiMateriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('rekomendasi_materi')]
#[Fillable(['pretest_id', 'user_id', 'materi_id', 'prioritas'])]
#[UseFactory(RekomendasiMateriFactory::class)]
class RekomendasiMateri extends Model
{
    /** @use HasFactory<RekomendasiMateriFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Pretest, $this>
     */
    public function pretest(): BelongsTo
    {
        return $this->belongsTo(Pretest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Materi, $this>
     */
    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }
}
