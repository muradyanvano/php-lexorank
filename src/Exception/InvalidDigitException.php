<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Exception;

final class InvalidDigitException extends LexoRankException
{
    public static function character(string $char): self
    {
        return new self(sprintf(
            'Invalid digit character "%s". Allowed alphabet is 0-9a-z (base 36).',
            $char,
        ));
    }

    public static function value(int $digit, int $base): self
    {
        return new self(sprintf(
            'Digit value %d is outside the valid range 0..%d for base %d.',
            $digit,
            $base - 1,
            $base,
        ));
    }
}
