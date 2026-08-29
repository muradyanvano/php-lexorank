<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Laravel\Facades;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Facade;
use MuradyanVano\LexoRank\LexoRank as Rank;
use MuradyanVano\LexoRank\LexoRankBucket;
use MuradyanVano\LexoRank\LexoRankService;
use MuradyanVano\LexoRank\Rebalancer;
use MuradyanVano\LexoRank\RebalanceResult;

/**
 * Laravel facade for LexoRankService / Rebalancer.
 *
 * Named differently from the core {@see Rank} value object to avoid collisions.
 * Alias registered as `LexoRankFacade` in composer.json extra.laravel.aliases.
 *
 * @method static Rank               parse(string $rank)
 * @method static Rank|null          tryParse(string $rank)
 * @method static Rank               min(?LexoRankBucket $bucket = null)
 * @method static Rank               max(?LexoRankBucket $bucket = null)
 * @method static Rank               middle(?LexoRankBucket $bucket = null)
 * @method static Rank               initial(?LexoRankBucket $bucket = null)
 * @method static Rank               between(?Rank $lower, ?Rank $upper)
 * @method static list<Rank>         betweenMany(?Rank $lower, ?Rank $upper, int $count)
 * @method static list<Rank>         initialRanks(int $count, ?LexoRankBucket $bucket = null)
 * @method static list<string>       findDuplicates(iterable<int|string, string|Rank> $ranks)
 * @method static void               assertNoDuplicates(iterable<int|string, string|Rank> $ranks)
 * @method static int                maxRankLength()
 *
 * @see LexoRankService
 */
final class LexoRank extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LexoRankService::class;
    }

    /**
     * @param iterable<int|string, string|Rank> $ranks
     * @throws BindingResolutionException
     */
    public static function rebalance(iterable $ranks, ?LexoRankBucket $targetBucket = null): RebalanceResult
    {
        $app = static::$app;
        if ($app === null) {
            throw new \RuntimeException('A facade root has not been set.');
        }

        /** @var Rebalancer $rebalancer */
        $rebalancer = $app->make(Rebalancer::class);

        return $rebalancer->rebalance($ranks, $targetBucket);
    }
}
