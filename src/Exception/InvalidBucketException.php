<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Exception;

final class InvalidBucketException extends LexoRankException
{
    public static function unknown(string $bucket): self
    {
        return new self(sprintf(
            'Unknown bucket "%s". Allowed buckets are 0, 1, and 2.',
            $bucket,
        ));
    }

    public static function unknownId(int $id): self
    {
        return new self(sprintf(
            'Unknown bucket id %d. Allowed bucket ids are 0, 1, and 2.',
            $id,
        ));
    }
}
