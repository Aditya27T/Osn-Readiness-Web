<?php

namespace App\Exceptions;

use Exception;

/**
 * Induk semua kesalahan bisnis yang perlu dibedakan oleh frontend.
 *
 * Handler global di bootstrap/app.php merendernya menjadi
 * { "message", "kode", "detail" } dengan status HTTP dari exception.
 */
class BisnisException extends Exception
{
    public function __construct(
        string $message,
        protected readonly string $kode,
        protected readonly int $status = 409,
        protected readonly string $detail = '',
    ) {
        parent::__construct($message);
    }

    public function getKode(): string
    {
        return $this->kode;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }
}
