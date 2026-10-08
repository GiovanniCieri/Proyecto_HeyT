<?php

namespace App\Services\Vittles;

use RuntimeException;

class VittlesException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $httpStatus = null)
    {
        parent::__construct($message);
    }
}
