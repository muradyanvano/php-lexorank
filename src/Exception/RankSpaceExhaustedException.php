<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Exception;

use RuntimeException;

final class RankSpaceExhaustedException extends RuntimeException implements LexoRankExceptionInterface
{
    public static function beforeMin(string $rank): self
    {
        return new self(sprintf(
            'Cannot generate a rank before the minimum rank "%s". Rebalance into the next bucket.',
            $rank,
        ));
    }

    public static function afterMax(string $rank): self
    {
        return new self(sprintf(
            'Cannot generate a rank after the maximum rank "%s". Rebalance into the next bucket.',
            $rank,
        ));
    }

    public static function maxLength(int $maxLength): self
    {
        return new self(sprintf(
            'Generated rank would exceed the maximum serialized length of %d. Rebalance the collection.',
            $maxLength,
        ));
    }
}
