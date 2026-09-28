<?php

namespace App\Support;

use Closure;

/**
 * The 128-number face descriptors that the browser computes from a member's photo. They are
 * biometric data: stored only for login, never exposed in any response.
 */
class FaceDescriptors
{
    public const SIZE = 128;

    /**
     * Euclidean distance under which two descriptors count as the same person. Stricter than
     * the attendance scanner because a match here signs someone in.
     */
    public const LOGIN_THRESHOLD = 0.45;

    /**
     * Rule for a JSON-encoded descriptor sent by a form or scanner.
     */
    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (self::parse($value) === null) {
                $fail('Data wajah tidak valid.');
            }
        };
    }

    /**
     * @return list<float>|null
     */
    public static function parse(mixed $value): ?array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($decoded) || count($decoded) !== self::SIZE || ! array_is_list($decoded)) {
            return null;
        }

        foreach ($decoded as $number) {
            if ((! is_int($number) && ! is_float($number)) || ! is_finite($number) || abs($number) > 10) {
                return null;
            }
        }

        return array_map('floatval', $decoded);
    }

    /**
     * @param  list<float>  $first
     * @param  list<float>  $second
     */
    public static function distance(array $first, array $second): float
    {
        $sum = 0.0;

        foreach ($first as $index => $value) {
            $sum += ($value - $second[$index]) ** 2;
        }

        return sqrt($sum);
    }
}
