<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Exception;

final class InvalidBoundsException extends LexoRankException
{
    public static function equal(): self
    {
        return new self('Cannot generate a rank between equal bounds.');
    }

    public static function reversed(string $lower, string $upper): self
    {
        return new self(sprintf(
            'Bounds are reversed: lower "%s" is not strictly before upper "%s".',
            $lower,
            $upper,
        ));
    }

    public static function differentBuckets(string $leftBucket, string $rightBucket): self
    {
        return new self(sprintf(
            'Cannot generate a rank between different buckets ("%s" and "%s"). Use rebalancing to move ranks into a single bucket.',
            $leftBucket,
            $rightBucket,
        ));
    }
}
