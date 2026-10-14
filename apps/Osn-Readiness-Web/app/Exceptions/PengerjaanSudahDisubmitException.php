<?php

namespace App\Exceptions;

/**
 * Jawaban tidak bisa diubah lagi karena pengerjaan (pre-test, latihan, atau
 * simulasi) sudah disubmit.
 */
class PengerjaanSudahDisubmitException extends BisnisException
{
    public function __construct(string $detail = '', ?string $message = null)
    {
        parent::__construct($message ?? 'Jawaban sudah dikunci karena pengerjaan sudah disubmit.', 'SUDAH_DISUBMIT', 409, $detail);
    }
}
