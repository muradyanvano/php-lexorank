<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Exception;

final class InvalidRebalanceInputException extends LexoRankException
{
    public static function unsorted(string $previous, string $current): self
    {
        return new self(sprintf(
            'Rebalance input is not strictly ordered: "%s" is not before "%s".',
            $previous,
            $current,
        ));
    }

    public static function invalidCount(int $count): self
    {
        return new self(sprintf(
            'Count must be a positive integer, got %d.',
            $count,
        ));
    }

    public static function countTooLarge(int $count, int $max): self
    {
        return new self(sprintf(
            'Requested count %d exceeds the safety limit of %d.',
            $count,
            $max,
        ));
    }
}
