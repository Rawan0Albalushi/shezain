<?php

namespace App\Services\Thawani;

final class WebhookSignature
{
    public static function valid(string $rawBody, string $timestamp, string $signature, ?string $secret): bool
    {
        if ($secret === null || $secret === '' || $timestamp === '' || $signature === '') {
            return false;
        }

        if (ctype_digit($timestamp)) {
            $seconds = strlen($timestamp) > 10 ? (int) substr($timestamp, 0, 10) : (int) $timestamp;

            if (abs(time() - $seconds) > 300) {
                return false;
            }
        }

        $expected = hash_hmac('sha256', $rawBody.'-'.$timestamp, $secret);

        return hash_equals($expected, strtolower($signature));
    }
}
