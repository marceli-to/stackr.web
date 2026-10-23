<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Sortable position keys that always leave room between two neighbours, so
 * moving an item only rewrites that one item's key.
 *
 * Port of https://github.com/rocicorp/fractional-indexing. Keys compare byte
 * by byte, which is why the position columns use a binary collation.
 */
class FractionalIndex
{
    private const DIGITS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private const SMALLEST_INTEGER = 'A00000000000000000000000000';

    /**
     * A key that puts an item at $index among $keys, which must already be
     * sorted and must not include the item being placed.
     *
     * @param  array<int, string>  $keys
     */
    public static function forIndex(array $keys, int $index): string
    {
        $keys = array_values($keys);
        $index = max(0, min($index, count($keys)));

        return self::between($keys[$index - 1] ?? null, $keys[$index] ?? null);
    }

    /**
     * A key that sorts after $before and before $after. Either may be null
     * for the start or end of the list.
     */
    public static function between(?string $before, ?string $after): string
    {
        if ($before !== null) {
            self::validateKey($before);
        }

        if ($after !== null) {
            self::validateKey($after);
        }

        if ($before !== null && $after !== null && strcmp($before, $after) >= 0) {
            throw new InvalidArgumentException("Key [{$before}] must sort before [{$after}].");
        }

        if ($before === null) {
            if ($after === null) {
                return 'a0';
            }

            $integer = self::integerPart($after);
            $fraction = substr($after, strlen($integer));

            if ($integer === self::SMALLEST_INTEGER) {
                return $integer.self::midpoint('', $fraction);
            }

            if (strcmp($integer, $after) < 0) {
                return $integer;
            }

            return self::decrementInteger($integer)
                ?? throw new InvalidArgumentException('Cannot create a key before the smallest key.');
        }

        $integer = self::integerPart($before);
        $fraction = substr($before, strlen($integer));

        if ($after === null) {
            return self::incrementInteger($integer) ?? $integer.self::midpoint($fraction, null);
        }

        $afterInteger = self::integerPart($after);

        if ($integer === $afterInteger) {
            return $integer.self::midpoint($fraction, substr($after, strlen($afterInteger)));
        }

        $next = self::incrementInteger($integer)
            ?? throw new InvalidArgumentException('Cannot create a key after the largest key.');

        return strcmp($next, $after) < 0 ? $next : $integer.self::midpoint($fraction, null);
    }

    /**
     * A digit string strictly between fractions $a and $b ($b null = no upper bound).
     */
    private static function midpoint(string $a, ?string $b): string
    {
        $zero = self::DIGITS[0];

        if ($b !== null && strcmp($a, $b) >= 0) {
            throw new InvalidArgumentException("Fraction [{$a}] must sort before [{$b}].");
        }

        if (str_ends_with($a, $zero) || ($b !== null && str_ends_with($b, $zero))) {
            throw new InvalidArgumentException('Fractions cannot end in zero.');
        }

        if ($b !== null) {
            // Keep the shared prefix and find the midpoint of what follows.
            $n = 0;
            while ($n < strlen($b) && ($a[$n] ?? $zero) === $b[$n]) {
                $n++;
            }

            if ($n > 0) {
                return substr($b, 0, $n).self::midpoint(substr($a, $n), substr($b, $n));
            }
        }

        $digitA = $a === '' ? 0 : self::digit($a[0]);
        $digitB = $b === null ? strlen(self::DIGITS) : self::digit($b[0]);

        if ($digitB - $digitA > 1) {
            return self::DIGITS[(int) round(($digitA + $digitB) / 2)];
        }

        if ($b !== null && strlen($b) > 1) {
            return $b[0];
        }

        return self::DIGITS[$digitA].self::midpoint(substr($a, 1), null);
    }

    private static function validateKey(string $key): void
    {
        if ($key === self::SMALLEST_INTEGER || strspn($key, self::DIGITS) !== strlen($key)) {
            throw new InvalidArgumentException("Invalid position key [{$key}].");
        }

        $integer = self::integerPart($key);

        if (str_ends_with(substr($key, strlen($integer)), self::DIGITS[0])) {
            throw new InvalidArgumentException("Invalid position key [{$key}].");
        }
    }

    private static function digit(string $char): int
    {
        $digit = strpos(self::DIGITS, $char);

        if ($digit === false) {
            throw new InvalidArgumentException("Invalid position key digit [{$char}].");
        }

        return $digit;
    }

    /**
     * The head letter encodes how many digits the integer part has:
     * a–z grow upwards from 2, A–Z grow downwards from 2.
     */
    private static function integerLength(string $head): int
    {
        if ($head >= 'a' && $head <= 'z') {
            return ord($head) - ord('a') + 2;
        }

        if ($head >= 'A' && $head <= 'Z') {
            return ord('Z') - ord($head) + 2;
        }

        throw new InvalidArgumentException("Invalid position key head [{$head}].");
    }

    private static function integerPart(string $key): string
    {
        if ($key === '') {
            throw new InvalidArgumentException('Position keys cannot be empty.');
        }

        $length = self::integerLength($key[0]);

        if ($length > strlen($key)) {
            throw new InvalidArgumentException("Invalid position key [{$key}].");
        }

        return substr($key, 0, $length);
    }

    private static function incrementInteger(string $integer): ?string
    {
        $head = $integer[0];
        $digits = str_split(substr($integer, 1));
        $last = strlen(self::DIGITS) - 1;

        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $digit = self::digit($digits[$i]);

            if ($digit < $last) {
                $digits[$i] = self::DIGITS[$digit + 1];

                return $head.implode('', $digits);
            }

            $digits[$i] = self::DIGITS[0];
        }

        // Every digit carried over: move to the next head letter.
        if ($head === 'Z') {
            return 'a'.self::DIGITS[0];
        }

        if ($head === 'z') {
            return null;
        }

        $nextHead = chr(ord($head) + 1);

        if ($nextHead > 'a') {
            $digits[] = self::DIGITS[0];
        } else {
            array_pop($digits);
        }

        return $nextHead.implode('', $digits);
    }

    private static function decrementInteger(string $integer): ?string
    {
        $head = $integer[0];
        $digits = str_split(substr($integer, 1));
        $last = strlen(self::DIGITS) - 1;

        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $digit = self::digit($digits[$i]);

            if ($digit > 0) {
                $digits[$i] = self::DIGITS[$digit - 1];

                return $head.implode('', $digits);
            }

            $digits[$i] = self::DIGITS[$last];
        }

        // Every digit borrowed: move to the previous head letter.
        if ($head === 'a') {
            return 'Z'.self::DIGITS[$last];
        }

        if ($head === 'A') {
            return null;
        }

        $previousHead = chr(ord($head) - 1);

        if ($previousHead < 'Z') {
            $digits[] = self::DIGITS[$last];
        } else {
            array_pop($digits);
        }

        return $previousHead.implode('', $digits);
    }
}
