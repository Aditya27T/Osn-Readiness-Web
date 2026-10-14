<?php

namespace App\Contracts\Services;

use App\DTOs\AturanTingkat;

interface AturanServiceInterface
{
    /**
     * Aturan satu tingkat sebagai DTO bertipe. Hasilnya di-cache per request.
     */
    public function untukTingkat(int $tingkatId): AturanTingkat;
}
