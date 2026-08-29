<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Laravel\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use MuradyanVano\LexoRank\Exception\InvalidRankFormatException;
use MuradyanVano\LexoRank\Exception\LexoRankException;
use MuradyanVano\LexoRank\LexoRank;

/**
 * Eloquent cast that hydrates rank columns into LexoRank value objects.
 *
 * @implements CastsAttributes<LexoRank|null, LexoRank|string|null>
 */
final class LexoRankCast implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?LexoRank
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new LexoRankException(sprintf(
                'Rank column "%s" must be a string or null, got %s.',
                $key,
                get_debug_type($value),
            ));
        }

        return LexoRank::parse($value);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof LexoRank) {
            return $value->toString();
        }

        if (is_string($value)) {
            return LexoRank::parse($value)->toString();
        }

        throw InvalidRankFormatException::malformed(get_debug_type($value));
    }
}
