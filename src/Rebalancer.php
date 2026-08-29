<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank;

use MuradyanVano\LexoRank\Exception\DuplicateRankException;
use MuradyanVano\LexoRank\Exception\InvalidRebalanceInputException;

/**
 * Generates evenly distributed replacement ranks in the next bucket.
 *
 * Does not mutate persistence. Returns an explicit old→new mapping so callers
 * can apply updates transactionally.
 */
final class Rebalancer
{
    private LexoRankService $service;

    public function __construct(?LexoRankService $service = null)
    {
        $this->service = $service ?? new LexoRankService();
    }

    /**
     * @param iterable<int|string, string|LexoRank> $ranks ordered existing ranks
     * @param LexoRankBucket|null                   $targetBucket defaults to next bucket of the first rank (or bucket 1 if empty)
     */
    public function rebalance(iterable $ranks, ?LexoRankBucket $targetBucket = null): RebalanceResult
    {
        $parsed = [];
        foreach ($ranks as $rank) {
            $parsed[] = $rank instanceof LexoRank
                ? $rank
                : $this->service->parse((string) $rank);
        }

        if ($parsed === []) {
            $bucket = $targetBucket ?? LexoRankBucket::bucket1();

            return new RebalanceResult($bucket, [], []);
        }

        $this->assertStrictlyOrdered($parsed);

        $sourceBucket = $parsed[0]->bucket();
        $bucket = $targetBucket ?? $sourceBucket->next();

        $newRanks = $this->service->initialRanks(count($parsed), $bucket);
        $mapping = [];

        foreach ($parsed as $index => $old) {
            $mapping[$old->toString()] = $newRanks[$index]->toString();
        }

        return new RebalanceResult($bucket, $newRanks, $mapping);
    }

    /**
     * Suggest rebalancing when any rank exceeds the soft length threshold.
     *
     * @param iterable<string|LexoRank> $ranks
     */
    public function shouldRebalance(iterable $ranks, int $softLength = 64): bool
    {
        foreach ($ranks as $rank) {
            $value = $rank instanceof LexoRank ? $rank->toString() : (string) $rank;
            if (strlen($value) >= $softLength) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<LexoRank> $ranks
     */
    private function assertStrictlyOrdered(array $ranks): void
    {
        $seen = [];
        $previous = null;

        foreach ($ranks as $rank) {
            $key = $rank->toString();
            if (isset($seen[$key])) {
                throw DuplicateRankException::value($key);
            }
            $seen[$key] = true;

            if ($previous !== null && !$previous->isBefore($rank)) {
                throw InvalidRebalanceInputException::unsorted(
                    $previous->toString(),
                    $rank->toString(),
                );
            }
            $previous = $rank;
        }
    }
}
