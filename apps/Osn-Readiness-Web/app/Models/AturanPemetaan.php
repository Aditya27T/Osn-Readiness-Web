<?php

namespace App\Models;

use Database\Factories\AturanPemetaanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('aturan_pemetaan')]
#[Fillable(['tingkat_id', 'parameter', 'ketentuan'])]
#[UseFactory(AturanPemetaanFactory::class)]
class AturanPemetaan extends Model
{
    /** @use HasFactory<AturanPemetaanFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<TingkatSeleksi, $this>
     */
    public function tingkat(): BelongsTo
    {
        return $this->belongsTo(TingkatSeleksi::class);
    }
}
