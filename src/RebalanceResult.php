<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank;

/**
 * Result of a rebalance operation.
 *
 * The core package never writes to a database. Persist {@see mapping()} inside
 * an application transaction following the procedure in docs/rebalancing.md.
 */
final class RebalanceResult
{
    private LexoRankBucket $bucket;

    /** @var list<LexoRank> */
    private array $ranks;

    /** @var array<string, string> old canonical rank => new canonical rank */
    private array $mapping;

    /**
     * @param list<LexoRank>        $ranks
     * @param array<string, string> $mapping
     */
    public function __construct(LexoRankBucket $bucket, array $ranks, array $mapping)
    {
        $this->bucket = $bucket;
        $this->ranks = $ranks;
        $this->mapping = $mapping;
    }

    public function bucket(): LexoRankBucket
    {
        return $this->bucket;
    }

    /**
     * @return list<LexoRank>
     */
    public function ranks(): array
    {
        return $this->ranks;
    }

    /**
     * @return array<string, string>
     */
    public function mapping(): array
    {
        return $this->mapping;
    }

    public function count(): int
    {
        return count($this->ranks);
    }

    public function map(string $oldRank): ?string
    {
        return $this->mapping[$oldRank] ?? null;
    }
}
