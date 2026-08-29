<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank;

use MuradyanVano\LexoRank\Exception\DuplicateRankException;
use MuradyanVano\LexoRank\Exception\InvalidBoundsException;
use MuradyanVano\LexoRank\Exception\InvalidRebalanceInputException;
use MuradyanVano\LexoRank\Exception\RankSpaceExhaustedException;
use MuradyanVano\LexoRank\Math\NumeralSystem36;

/**
 * High-level LexoRank operations: bulk allocation, bounding helpers, and
 * collision utilities. Prefer this service for application orchestration;
 * use {@see LexoRank} value objects for single-rank math.
 */
final class LexoRankService
{
    public const DEFAULT_MAX_COUNT = 100_000;

    private int $maxCount;

    private int $maxRankLength;

    public function __construct(
        int $maxCount = self::DEFAULT_MAX_COUNT,
        int $maxRankLength = NumeralSystem36::DEFAULT_MAX_RANK_LENGTH,
    ) {
        $this->maxCount = $maxCount;
        $this->maxRankLength = $maxRankLength;
    }

    public function parse(string $rank): LexoRank
    {
        return LexoRank::parse($rank, $this->maxRankLength);
    }

    public function tryParse(string $rank): ?LexoRank
    {
        return LexoRank::tryParse($rank, $this->maxRankLength);
    }

    public function min(?LexoRankBucket $bucket = null): LexoRank
    {
        return LexoRank::min($bucket);
    }

    public function max(?LexoRankBucket $bucket = null): LexoRank
    {
        return LexoRank::max($bucket);
    }

    public function middle(?LexoRankBucket $bucket = null): LexoRank
    {
        return LexoRank::middle($bucket);
    }

    public function initial(?LexoRankBucket $bucket = null): LexoRank
    {
        return LexoRank::initial($bucket);
    }

    public function between(?LexoRank $lower, ?LexoRank $upper): LexoRank
    {
        if ($lower === null && $upper === null) {
            return LexoRank::middle();
        }

        if ($lower === null) {
            /** @var LexoRank $upper */
            return $upper->before();
        }

        if ($upper === null) {
            return $lower->after();
        }

        if ($lower->equals($upper)) {
            throw InvalidBoundsException::equal();
        }

        if ($lower->isAfter($upper)) {
            throw InvalidBoundsException::reversed($lower->toString(), $upper->toString());
        }

        $result = $lower->between($upper);
        $this->assertLength($result);

        return $result;
    }

    /**
     * Generate exactly $count strictly ordered unique ranks between optional bounds.
     *
     * Distribution uses recursive midpoint partitioning so ranks are more evenly
     * spaced than repeatedly inserting at one end of a shrinking gap.
     *
     * @return list<LexoRank>
     */
    public function betweenMany(?LexoRank $lower, ?LexoRank $upper, int $count): array
    {
        $this->assertPositiveCount($count);

        if ($lower !== null && $upper !== null) {
            if ($lower->equals($upper)) {
                throw InvalidBoundsException::equal();
            }
            if ($lower->isAfter($upper)) {
                throw InvalidBoundsException::reversed($lower->toString(), $upper->toString());
            }
            if (!$lower->bucket()->equals($upper->bucket())) {
                throw InvalidBoundsException::differentBuckets(
                    $lower->bucket()->toString(),
                    $upper->bucket()->toString(),
                );
            }
        }

        $bucket = $lower?->bucket() ?? $upper?->bucket() ?? LexoRankBucket::bucket0();
        $effectiveLower = $lower ?? LexoRank::min($bucket);
        $effectiveUpper = $upper ?? LexoRank::max($bucket);

        // When only one bound is provided, leave room at the open end using
        // min/max sentinels so generated ranks stay strictly inside the open side.
        if ($lower === null && $upper !== null) {
            $effectiveLower = LexoRank::min($bucket);
        }
        if ($lower !== null && $upper === null) {
            $effectiveUpper = LexoRank::max($bucket);
        }

        $ranks = $this->allocateBetween($effectiveLower, $effectiveUpper, $count);

        foreach ($ranks as $rank) {
            $this->assertLength($rank);
        }

        return $ranks;
    }

    /**
     * Generate $count evenly distributed initial ranks in the given bucket.
     *
     * @return list<LexoRank>
     */
    public function initialRanks(int $count, ?LexoRankBucket $bucket = null): array
    {
        $this->assertPositiveCount($count);
        $bucket ??= LexoRankBucket::bucket0();

        return $this->betweenMany(LexoRank::min($bucket), LexoRank::max($bucket), $count);
    }

    /**
     * @param iterable<string|LexoRank> $ranks
     *
     * @return list<string>
     */
    public function findDuplicates(iterable $ranks): array
    {
        $seen = [];
        $duplicates = [];

        foreach ($ranks as $rank) {
            $value = $rank instanceof LexoRank ? $rank->toString() : (string) $rank;
            if (isset($seen[$value])) {
                $duplicates[$value] = $value;
            } else {
                $seen[$value] = true;
            }
        }

        return array_values($duplicates);
    }

    /**
     * @param iterable<string|LexoRank> $ranks
     *
     * @throws DuplicateRankException
     */
    public function assertNoDuplicates(iterable $ranks): void
    {
        $duplicates = $this->findDuplicates($ranks);
        if ($duplicates !== []) {
            throw DuplicateRankException::value($duplicates[0]);
        }
    }

    public function maxRankLength(): int
    {
        return $this->maxRankLength;
    }

    /**
     * @return list<LexoRank>
     */
    private function allocateBetween(LexoRank $lower, LexoRank $upper, int $count): array
    {
        if ($count === 1) {
            return [$lower->between($upper)];
        }

        // Binary tree partitioning: place the middle rank first, then recurse.
        $midIndex = intdiv($count, 2);
        $mid = $lower->between($upper);

        $leftCount = $midIndex;
        $rightCount = $count - $midIndex - 1;

        $left = $leftCount > 0 ? $this->allocateBetween($lower, $mid, $leftCount) : [];
        $right = $rightCount > 0 ? $this->allocateBetween($mid, $upper, $rightCount) : [];

        return array_merge($left, [$mid], $right);
    }

    private function assertPositiveCount(int $count): void
    {
        if ($count < 1) {
            throw InvalidRebalanceInputException::invalidCount($count);
        }

        if ($count > $this->maxCount) {
            throw InvalidRebalanceInputException::countTooLarge($count, $this->maxCount);
        }
    }

    private function assertLength(LexoRank $rank): void
    {
        if ($rank->length() > $this->maxRankLength) {
            throw RankSpaceExhaustedException::maxLength($this->maxRankLength);
        }
    }
}
