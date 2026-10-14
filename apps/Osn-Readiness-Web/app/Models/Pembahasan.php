<?php

namespace App\Models;

use Database\Factories\PembahasanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pembahasan')]
#[Fillable(['soal_id', 'isi_pembahasan'])]
#[UseFactory(PembahasanFactory::class)]
class Pembahasan extends Model
{
    /** @use HasFactory<PembahasanFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Soal, $this>
     */
    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
