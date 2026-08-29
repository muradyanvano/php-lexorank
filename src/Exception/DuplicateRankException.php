<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Exception;

final class DuplicateRankException extends LexoRankException
{
    public static function value(string $rank): self
    {
        return new self(sprintf('Duplicate rank detected: "%s".', $rank));
    }
}
