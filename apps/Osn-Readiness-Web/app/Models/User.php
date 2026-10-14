<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active', 'tingkat_aktif_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TingkatSeleksi, $this>
     */
    public function tingkatAktif(): BelongsTo
    {
        return $this->belongsTo(TingkatSeleksi::class, 'tingkat_aktif_id');
    }

    /**
     * @return HasMany<Pretest, $this>
     */
    public function pretest(): HasMany
    {
        return $this->hasMany(Pretest::class);
    }

    /**
     * @return HasMany<KenaikanTingkat, $this>
     */
    public function kenaikanTingkat(): HasMany
    {
        return $this->hasMany(KenaikanTingkat::class);
    }

    /**
     * @return HasMany<ProgressBelajar, $this>
     */
    public function progressBelajar(): HasMany
    {
        return $this->hasMany(ProgressBelajar::class);
    }
}
