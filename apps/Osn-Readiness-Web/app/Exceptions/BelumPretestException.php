<?php

namespace App\Exceptions;

class BelumPretestException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Pre-test putaran ini belum selesai.', 'BELUM_PRETEST', 409, $detail);
    }
}
