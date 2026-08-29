<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Tests\Unit;

use MuradyanVano\LexoRank\LexoRank;
use MuradyanVano\LexoRank\LexoRankService;
use PHPUnit\Framework\TestCase;

/**
 * Deterministic property / invariant tests with a fixed RNG seed.
 */
final class InvariantTest extends TestCase
{
    private const SEED = 20260829;

    public function test_between_invariant_for_random_pairs(): void
    {
        mt_srand(self::SEED);
        $service = new LexoRankService();
        $pool = $service->initialRanks(100);

        for ($i = 0; $i < 200; ++$i) {
            $a = $pool[mt_rand(0, 98)];
            $b = $pool[mt_rand(0, 98)];
            if ($a->equals($b)) {
                continue;
            }
            if ($a->isAfter($b)) {
                [$a, $b] = [$b, $a];
            }

            $mid = $a->between($b);
            self::assertTrue($a->isBefore($mid), "lower < mid failed for {$a} / {$b}");
            self::assertTrue($mid->isBefore($b), "mid < upper failed for {$a} / {$b}");
            self::assertTrue(LexoRank::parse($mid->toString())->equals($mid));
        }
    }

    public function test_compare_antisymmetry_random(): void
    {
        mt_srand(self::SEED);
        $pool = (new LexoRankService())->initialRanks(80);

        for ($i = 0; $i < 300; ++$i) {
            $a = $pool[mt_rand(0, 79)];
            $b = $pool[mt_rand(0, 79)];
            self::assertSame(-$b->compareTo($a), $a->compareTo($b));
        }
    }

    public function test_normalization_idempotent(): void
    {
        $ranks = (new LexoRankService())->initialRanks(30);
        foreach ($ranks as $rank) {
            $once = LexoRank::parse($rank->toString());
            $twice = LexoRank::parse($once->toString());
            self::assertSame($once->toString(), $twice->toString());
            self::assertTrue($once->equals($twice));
        }
    }

    public function test_thousands_of_sequential_appends_remain_ordered(): void
    {
        $current = LexoRank::initial();
        $previous = null;

        for ($i = 0; $i < 1000; ++$i) {
            if ($previous !== null) {
                self::assertTrue($previous->isBefore($current));
            }
            $previous = $current;
            $current = $current->after();
        }
    }

    public function test_deep_repeated_between_same_gap(): void
    {
        $lower = LexoRank::parse('0|i00000:');
        $upper = LexoRank::parse('0|i00001:');

        for ($i = 0; $i < 100; ++$i) {
            $mid = $lower->between($upper);
            self::assertTrue($lower->isBefore($mid));
            self::assertTrue($mid->isBefore($upper));
            $lower = $mid;
        }
    }
}
