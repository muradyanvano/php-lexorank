<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Tests\Integration;

use MuradyanVano\LexoRank\LexoRank;
use MuradyanVano\LexoRank\LexoRankService;
use MuradyanVano\LexoRank\Rebalancer;
use PHPUnit\Framework\TestCase;

/**
 * Representative performance / scale checks (not a micro-benchmark suite).
 */
final class PerformanceTest extends TestCase
{
    public function test_parse_and_compare_ten_thousand_ranks(): void
    {
        $service = new LexoRankService();
        $ranks = $service->initialRanks(200);
        $strings = array_map(static fn (LexoRank $r): string => $r->toString(), $ranks);

        $parsed = [];
        $start = hrtime(true);
        for ($i = 0; $i < 10_000; ++$i) {
            $parsed[] = LexoRank::parse($strings[$i % 200]);
        }
        for ($i = 1; $i < 10_000; ++$i) {
            $parsed[$i - 1]->compareTo($parsed[$i]);
        }
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        self::assertCount(10_000, $parsed);
        self::assertLessThan(15_000, $elapsedMs, 'parse+compare of 10k should finish under 15s');
    }

    public function test_rebalance_large_list(): void
    {
        $service = new LexoRankService();
        $ranks = $service->initialRanks(1000);
        $result = (new Rebalancer($service))->rebalance($ranks);

        self::assertSame(1000, $result->count());
        self::assertCount(1000, $result->mapping());
    }

    public function test_bulk_generation_of_many_ranks(): void
    {
        $ranks = (new LexoRankService())->initialRanks(500);
        self::assertCount(500, $ranks);
        self::assertTrue($ranks[0]->isBefore($ranks[499]));
    }
}
