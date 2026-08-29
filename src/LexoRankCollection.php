<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank;

use MuradyanVano\LexoRank\Exception\DuplicateRankException;
use MuradyanVano\LexoRank\Exception\InvalidRebalanceInputException;

/**
 * Lightweight helpers for working with ordered collections of ranks.
 *
 * This is intentionally not a full collection framework — just the operations
 * that add real value on top of plain PHP arrays.
 */
final class LexoRankCollection
{
    /** @var list<LexoRank> */
    private array $ranks;

    /**
     * @param list<LexoRank|string> $ranks
     */
    public function __construct(array $ranks = [])
    {
        $parsed = [];
        foreach ($ranks as $rank) {
            $parsed[] = $rank instanceof LexoRank ? $rank : LexoRank::parse($rank);
        }
        $this->ranks = $parsed;
    }

    /**
     * @param list<LexoRank|string> $ranks
     */
    public static function from(array $ranks): self
    {
        return new self($ranks);
    }

    /**
     * @return list<LexoRank>
     */
    public function all(): array
    {
        return $this->ranks;
    }

    public function count(): int
    {
        return count($this->ranks);
    }

    public function isEmpty(): bool
    {
        return $this->ranks === [];
    }

    public function sorted(): self
    {
        $copy = $this->ranks;
        usort($copy, static fn (LexoRank $a, LexoRank $b): int => $a->compareTo($b));

        return new self($copy);
    }

    /**
     * @return list<string>
     */
    public function duplicates(): array
    {
        return (new LexoRankService())->findDuplicates($this->ranks);
    }

    public function assertStrictlyOrdered(): void
    {
        $previous = null;
        $seen = [];

        foreach ($this->ranks as $rank) {
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

    public function isStrictlyOrdered(): bool
    {
        try {
            $this->assertStrictlyOrdered();

            return true;
        } catch (DuplicateRankException|InvalidRebalanceInputException) {
            return false;
        }
    }

    public function maxLength(): int
    {
        $max = 0;
        foreach ($this->ranks as $rank) {
            $max = max($max, $rank->length());
        }

        return $max;
    }

    public function suggestsRebalance(int $softLength = 64): bool
    {
        return $this->maxLength() >= $softLength;
    }

    /**
     * Rank for inserting at the given index (0 = before first, count = after last).
     */
    public function rankForInsertAt(int $index, ?LexoRankService $service = null): LexoRank
    {
        $service ??= new LexoRankService();
        $count = count($this->ranks);

        if ($index < 0 || $index > $count) {
            throw InvalidRebalanceInputException::invalidCount($index);
        }

        $lower = $index === 0 ? null : $this->ranks[$index - 1];
        $upper = $index === $count ? null : $this->ranks[$index];

        return $service->between($lower, $upper);
    }
}
