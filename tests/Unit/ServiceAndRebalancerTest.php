<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Tests\Unit;

use MuradyanVano\LexoRank\Exception\DuplicateRankException;
use MuradyanVano\LexoRank\Exception\InvalidBoundsException;
use MuradyanVano\LexoRank\Exception\InvalidRebalanceInputException;
use MuradyanVano\LexoRank\LexoRank;
use MuradyanVano\LexoRank\LexoRankBucket;
use MuradyanVano\LexoRank\LexoRankCollection;
use MuradyanVano\LexoRank\LexoRankService;
use MuradyanVano\LexoRank\Rebalancer;
use PHPUnit\Framework\TestCase;

final class ServiceAndRebalancerTest extends TestCase
{
    private LexoRankService $service;

    private Rebalancer $rebalancer;

    protected function setUp(): void
    {
        $this->service = new LexoRankService();
        $this->rebalancer = new Rebalancer($this->service);
    }

    public function test_initial_ranks_are_strictly_ordered_and_unique(): void
    {
        $ranks = $this->service->initialRanks(50);
        self::assertCount(50, $ranks);

        for ($i = 1; $i < 50; ++$i) {
            self::assertTrue($ranks[$i - 1]->isBefore($ranks[$i]));
        }

        $strings = array_map(static fn (LexoRank $r): string => $r->toString(), $ranks);
        self::assertCount(50, array_unique($strings));
    }

    public function test_between_many_with_bounds(): void
    {
        $lower = LexoRank::parse('0|100000:');
        $upper = LexoRank::parse('0|900000:');
        $ranks = $this->service->betweenMany($lower, $upper, 10);

        self::assertCount(10, $ranks);
        self::assertTrue($lower->isBefore($ranks[0]));
        self::assertTrue($ranks[9]->isBefore($upper));

        for ($i = 1; $i < 10; ++$i) {
            self::assertTrue($ranks[$i - 1]->isBefore($ranks[$i]));
        }
    }

    public function test_between_many_null_bounds(): void
    {
        $upper = LexoRank::middle();
        $before = $this->service->betweenMany(null, $upper, 5);
        self::assertCount(5, $before);
        self::assertTrue($before[4]->isBefore($upper));

        $lower = LexoRank::middle();
        $after = $this->service->betweenMany($lower, null, 5);
        self::assertCount(5, $after);
        self::assertTrue($lower->isBefore($after[0]));

        $open = $this->service->between(null, null);
        self::assertSame(LexoRank::middle()->toString(), $open->toString());
    }

    public function test_between_many_rejects_invalid_count(): void
    {
        $this->expectException(InvalidRebalanceInputException::class);
        $this->service->initialRanks(0);
    }

    public function test_between_rejects_reversed(): void
    {
        $this->expectException(InvalidBoundsException::class);
        $this->service->between(LexoRank::max(), LexoRank::min());
    }

    public function test_find_duplicates(): void
    {
        $dupes = $this->service->findDuplicates([
            '0|100000:',
            '0|200000:',
            '0|100000:',
        ]);
        self::assertSame(['0|100000:'], $dupes);

        $this->expectException(DuplicateRankException::class);
        $this->service->assertNoDuplicates(['0|a:', '0|a:']);
    }

    public function test_rebalance_empty_and_single(): void
    {
        $empty = $this->rebalancer->rebalance([]);
        self::assertSame(0, $empty->count());
        self::assertSame('1', $empty->bucket()->toString());

        $single = $this->rebalancer->rebalance([LexoRank::middle()]);
        self::assertSame(1, $single->count());
        self::assertSame('1', $single->bucket()->toString());
        self::assertArrayHasKey('0|hzzzzz:', $single->mapping());
    }

    public function test_rebalance_preserves_order_and_rotates_bucket(): void
    {
        $ranks = $this->service->initialRanks(20);
        $result = $this->rebalancer->rebalance($ranks);

        self::assertSame(20, $result->count());
        self::assertTrue($result->bucket()->equals(LexoRankBucket::bucket1()));

        $newRanks = $result->ranks();
        for ($i = 1; $i < 20; ++$i) {
            self::assertTrue($newRanks[$i - 1]->isBefore($newRanks[$i]));
        }

        foreach ($ranks as $i => $old) {
            self::assertSame($newRanks[$i]->toString(), $result->map($old->toString()));
        }
    }

    public function test_rebalance_rejects_duplicates(): void
    {
        $this->expectException(DuplicateRankException::class);
        $this->rebalancer->rebalance([
            LexoRank::middle(),
            LexoRank::middle(),
        ]);
    }

    public function test_rebalance_rejects_unsorted(): void
    {
        $this->expectException(InvalidRebalanceInputException::class);
        $this->rebalancer->rebalance([
            LexoRank::max(),
            LexoRank::min(),
        ]);
    }

    public function test_rebalance_is_deterministic(): void
    {
        $ranks = $this->service->initialRanks(15);
        $a = $this->rebalancer->rebalance($ranks);
        $b = $this->rebalancer->rebalance($ranks);
        self::assertSame($a->mapping(), $b->mapping());
    }

    public function test_collection_sort_and_insert(): void
    {
        $collection = LexoRankCollection::from([
            '0|300000:',
            '0|100000:',
            '0|200000:',
        ])->sorted();

        self::assertTrue($collection->isStrictlyOrdered());
        self::assertSame('0|100000:', $collection->all()[0]->toString());

        $insert = $collection->rankForInsertAt(1);
        self::assertTrue($collection->all()[0]->isBefore($insert));
        self::assertTrue($insert->isBefore($collection->all()[1]));
    }

    public function test_should_rebalance_by_length(): void
    {
        self::assertFalse($this->rebalancer->shouldRebalance(['0|hzzzzz:'], 64));
        self::assertTrue($this->rebalancer->shouldRebalance(['0|' . str_repeat('z', 70) . ':'], 64));
    }
}
