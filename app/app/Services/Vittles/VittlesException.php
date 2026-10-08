<?php

namespace App\Services\Vittles;

use RuntimeException;

class VittlesException extends RuntimeException
{
    /** Adjunta el HTTP observado al error para mostrarlo y diagnosticarlo sin perder contexto. */
    public function __construct(string $message, public readonly ?int $httpStatus = null)
    {
        parent::__construct($message);
    }
}
