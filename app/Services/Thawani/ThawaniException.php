<?php

namespace App\Services\Thawani;

use RuntimeException;

class ThawaniException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(string $message, public readonly array $payload = [])
    {
        parent::__construct($message);
    }
}
