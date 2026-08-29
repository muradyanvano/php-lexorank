<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Exception;

final class InvalidRankFormatException extends LexoRankException
{
    public static function empty(): self
    {
        return new self('Rank string must not be empty.');
    }

    public static function tooLong(int $length, int $max): self
    {
        return new self(sprintf(
            'Rank string length %d exceeds the maximum allowed length of %d.',
            $length,
            $max,
        ));
    }

    public static function malformed(string $rank): self
    {
        return new self(sprintf(
            'Malformed rank string "%s". Expected format bucket|value: (e.g. "0|hzzzzz:").',
            self::truncate($rank),
        ));
    }

    public static function whitespace(string $rank): self
    {
        return new self(sprintf(
            'Rank string "%s" must not contain leading, trailing, or internal whitespace.',
            self::truncate($rank),
        ));
    }

    private static function truncate(string $value): string
    {
        if (strlen($value) <= 64) {
            return $value;
        }

        return substr($value, 0, 61) . '...';
    }
}
