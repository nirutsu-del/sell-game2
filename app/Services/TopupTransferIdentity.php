<?php

namespace App\Services;

class TopupTransferIdentity
{
    public static function key(string $method, string $reference): string
    {
        return hash('sha256', $method."\0".mb_strtoupper(trim($reference), 'UTF-8'));
    }
}
