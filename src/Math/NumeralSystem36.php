<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Math;

use MuradyanVano\LexoRank\Exception\InvalidDigitException;

/**
 * Base-36 numeral system used by LexoRank (alphabet 0-9a-z).
 *
 * @internal
 */
final class NumeralSystem36
{
    public const BASE = 36;

    public const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyz';

    public const RADIX_POINT = ':';

    public const POSITIVE_CHAR = '+';

    public const NEGATIVE_CHAR = '-';

    /** Fixed integer-part width used when serializing ranks. */
    public const INTEGER_WIDTH = 6;

    /** Default step used by before()/after() generation. */
    public const STEP = 8;

    /**
     * Soft ceiling for serialized rank length to prevent pathological growth.
     * Jira starts scheduling rebalance near 128; hard-stop guidance is ~254.
     */
    public const DEFAULT_MAX_RANK_LENGTH = 255;

    public static function base(): int
    {
        return self::BASE;
    }

    public static function toChar(int $digit): string
    {
        if ($digit < 0 || $digit >= self::BASE) {
            throw InvalidDigitException::value($digit, self::BASE);
        }

        return substr(self::ALPHABET, $digit, 1);
    }

    public static function toDigit(string $char): int
    {
        if (strlen($char) !== 1) {
            throw InvalidDigitException::character($char);
        }

        $pos = strpos(self::ALPHABET, $char);
        if ($pos === false) {
            throw InvalidDigitException::character($char);
        }

        return $pos;
    }

    public static function isValidDigit(string $char): bool
    {
        return strlen($char) === 1 && strpos(self::ALPHABET, $char) !== false;
    }
}
