<?php

namespace App\Models;

use App\Enums\StatusProgress;
use Database\Factories\ProgressBelajarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('progress_belajar')]
#[Fillable(['user_id', 'materi_id', 'status', 'persentase', 'tanggal_selesai'])]
#[UseFactory(ProgressBelajarFactory::class)]
class ProgressBelajar extends Model
{
    /** @use HasFactory<ProgressBelajarFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusProgress::class,
            'tanggal_selesai' => 'datetime',
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
     * @return BelongsTo<Materi, $this>
     */
    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }
}
