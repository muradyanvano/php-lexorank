<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Laravel\Concerns;

use Illuminate\Database\Eloquent\Builder;
use MuradyanVano\LexoRank\LexoRank;
use MuradyanVano\LexoRank\LexoRankService;

/**
 * Optional Eloquent helpers for models that store a LexoRank column.
 *
 * Rank-calculation methods do not persist. Persistence helpers are explicitly
 * named (*AndSave) so there is no hidden I/O.
 *
 * Configure the column via the model property `$lexoRankColumn` (default: `rank`).
 *
 * @phpstan-ignore-next-line trait.unused — consumed by application models
 */
trait HasLexoRank
{
    public function getLexoRankColumn(): string
    {
        return property_exists($this, 'lexoRankColumn')
            ? (string) $this->lexoRankColumn
            : 'rank';
    }

    public function getLexoRank(): ?LexoRank
    {
        $value = $this->getAttribute($this->getLexoRankColumn());

        if ($value instanceof LexoRank) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return LexoRank::parse((string) $value);
    }

    public function setLexoRank(LexoRank|string|null $rank): static
    {
        if ($rank instanceof LexoRank) {
            $this->setAttribute($this->getLexoRankColumn(), $rank->toString());
        } elseif ($rank === null) {
            $this->setAttribute($this->getLexoRankColumn(), null);
        } else {
            $this->setAttribute($this->getLexoRankColumn(), LexoRank::parse($rank)->toString());
        }

        return $this;
    }

    /**
     * Compute a new rank before another model's rank (does not save).
     */
    public function moveBefore(self $other): static
    {
        $otherRank = $other->getLexoRank();
        if ($otherRank === null) {
            return $this->setLexoRank(LexoRank::middle());
        }

        return $this->setLexoRank($otherRank->before());
    }

    /**
     * Compute a new rank after another model's rank (does not save).
     */
    public function moveAfter(self $other): static
    {
        $otherRank = $other->getLexoRank();
        if ($otherRank === null) {
            return $this->setLexoRank(LexoRank::middle());
        }

        return $this->setLexoRank($otherRank->after());
    }

    /**
     * Compute a new rank between two neighbours (does not save).
     * Pass null for open ends (first/last position).
     */
    public function moveBetween(?self $before, ?self $after, ?LexoRankService $service = null): static
    {
        $service ??= new LexoRankService();
        $lower = $before?->getLexoRank();
        $upper = $after?->getLexoRank();

        return $this->setLexoRank($service->between($lower, $upper));
    }

    public function moveBeforeAndSave(self $other): static
    {
        $this->moveBefore($other);
        $this->save();

        return $this;
    }

    public function moveAfterAndSave(self $other): static
    {
        $this->moveAfter($other);
        $this->save();

        return $this;
    }

    public function moveBetweenAndSave(?self $before, ?self $after, ?LexoRankService $service = null): static
    {
        $this->moveBetween($before, $after, $service);
        $this->save();

        return $this;
    }

    /**
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeOrderByRank(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy($this->getLexoRankColumn(), $direction);
    }
}
