<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Tests\Unit;

use MuradyanVano\LexoRank\Exception\InvalidBoundsException;
use MuradyanVano\LexoRank\Exception\InvalidBucketException;
use MuradyanVano\LexoRank\Exception\InvalidRankFormatException;
use MuradyanVano\LexoRank\Exception\RankSpaceExhaustedException;
use MuradyanVano\LexoRank\LexoRank;
use MuradyanVano\LexoRank\LexoRankBucket;
use PHPUnit\Framework\TestCase;

final class LexoRankTest extends TestCase
{
    public function test_min_middle_max_are_ordered(): void
    {
        $min = LexoRank::min();
        $middle = LexoRank::middle();
        $max = LexoRank::max();

        self::assertSame('0|000000:', $min->toString());
        self::assertSame('0|hzzzzz:', $middle->toString());
        self::assertSame('0|zzzzzz:', $max->toString());
        self::assertTrue($min->isBefore($middle));
        self::assertTrue($middle->isBefore($max));
    }

    public function test_parse_round_trip(): void
    {
        foreach (['0|000000:', '0|hzzzzz:', '0|zzzzzz:', '1|100000:', '2|i0000z:a'] as $raw) {
            $rank = LexoRank::parse($raw);
            self::assertSame($raw, $rank->toString());
            self::assertTrue(LexoRank::parse($rank->toString())->equals($rank));
        }
    }

    public function test_try_parse_returns_null_for_invalid(): void
    {
        self::assertNull(LexoRank::tryParse(''));
        self::assertNull(LexoRank::tryParse('bad'));
        self::assertNull(LexoRank::tryParse('3|000000:'));
        self::assertNull(LexoRank::tryParse('0|ABCDEF:'));
        self::assertNull(LexoRank::tryParse('0|000000'));
        self::assertNull(LexoRank::tryParse(' 0|000000:'));
    }

    public function test_parse_rejects_empty_and_whitespace(): void
    {
        $this->expectException(InvalidRankFormatException::class);
        LexoRank::parse('');
    }

    public function test_parse_rejects_whitespace(): void
    {
        $this->expectException(InvalidRankFormatException::class);
        LexoRank::parse("0|000000:\n");
    }

    public function test_parse_rejects_invalid_bucket(): void
    {
        $this->expectException(InvalidRankFormatException::class);
        LexoRank::parse('9|000000:');
    }

    public function test_parse_rejects_uppercase(): void
    {
        $this->expectException(InvalidRankFormatException::class);
        LexoRank::parse('0|HZZZZZ:');
    }

    public function test_between_returns_strictly_inside(): void
    {
        $lower = LexoRank::parse('0|100000:');
        $upper = LexoRank::parse('0|200000:');
        $mid = $lower->between($upper);

        self::assertTrue($lower->isBefore($mid));
        self::assertTrue($mid->isBefore($upper));
    }

    public function test_between_rejects_equal(): void
    {
        $rank = LexoRank::middle();
        $this->expectException(InvalidBoundsException::class);
        $rank->between($rank);
    }

    public function test_between_rejects_different_buckets(): void
    {
        $a = LexoRank::middle(LexoRankBucket::bucket0());
        $b = LexoRank::middle(LexoRankBucket::bucket1());
        $this->expectException(InvalidBoundsException::class);
        $a->between($b);
    }

    public function test_between_handles_adjacent_by_expanding_precision(): void
    {
        $left = LexoRank::parse('0|i00000:');
        $right = LexoRank::parse('0|i00001:');
        $mid = $left->between($right);

        self::assertTrue($left->isBefore($mid));
        self::assertTrue($mid->isBefore($right));
        // Adjacent integer cores require a fractional suffix.
        self::assertStringContainsString(':', $mid->toString());
        self::assertNotSame($left->toString(), $mid->toString());
    }

    public function test_before_and_after(): void
    {
        $mid = LexoRank::middle();
        $before = $mid->before();
        $after = $mid->after();

        self::assertTrue($before->isBefore($mid));
        self::assertTrue($mid->isBefore($after));
    }

    public function test_before_min_throws(): void
    {
        $this->expectException(RankSpaceExhaustedException::class);
        LexoRank::min()->before();
    }

    public function test_after_max_throws(): void
    {
        $this->expectException(RankSpaceExhaustedException::class);
        LexoRank::max()->after();
    }

    public function test_compare_to_antisymmetry_and_transitivity(): void
    {
        $a = LexoRank::parse('0|100000:');
        $b = LexoRank::parse('0|200000:');
        $c = LexoRank::parse('0|300000:');

        self::assertSame(-$b->compareTo($a), $a->compareTo($b));
        self::assertTrue($a->isBefore($b) && $b->isBefore($c) && $a->isBefore($c));
        self::assertSame(0, $a->compareTo($a));
    }

    public function test_string_comparison_matches_compare_to(): void
    {
        $ranks = [
            LexoRank::min(),
            LexoRank::parse('0|100000:'),
            LexoRank::middle(),
            LexoRank::parse('0|i00000:'),
            LexoRank::max(),
        ];

        for ($i = 0; $i < count($ranks); ++$i) {
            for ($j = 0; $j < count($ranks); ++$j) {
                self::assertSame(
                    $ranks[$i]->compareTo($ranks[$j]),
                    strcmp($ranks[$i]->toString(), $ranks[$j]->toString()),
                );
            }
        }
    }

    public function test_bucket_rotation(): void
    {
        $b0 = LexoRankBucket::bucket0();
        self::assertTrue($b0->next()->equals(LexoRankBucket::bucket1()));
        self::assertTrue($b0->next()->next()->equals(LexoRankBucket::bucket2()));
        self::assertTrue($b0->next()->next()->next()->equals($b0));
        self::assertTrue($b0->previous()->equals(LexoRankBucket::bucket2()));
    }

    public function test_invalid_bucket(): void
    {
        $this->expectException(InvalidBucketException::class);
        LexoRankBucket::fromString('3');
    }

    public function test_in_next_bucket_preserves_decimal(): void
    {
        $rank = LexoRank::middle();
        $moved = $rank->inNextBucket();
        self::assertSame('1', $moved->bucket()->toString());
        self::assertSame('1|hzzzzz:', $moved->toString());
    }

    public function test_repeated_midpoint_insertion_stays_ordered(): void
    {
        $lower = LexoRank::min();
        $upper = LexoRank::max();
        $current = $lower;

        for ($i = 0; $i < 200; ++$i) {
            $next = $current->between($upper);
            self::assertTrue($current->isBefore($next));
            self::assertTrue($next->isBefore($upper));
            $current = $next;
        }
    }

    public function test_initial_differs_by_bucket(): void
    {
        self::assertSame('0|100000:', LexoRank::initial(LexoRankBucket::bucket0())->toString());
        self::assertSame('1|y00000:', LexoRank::initial(LexoRankBucket::bucket1())->toString());
    }

    public function test_cast_to_string(): void
    {
        $rank = LexoRank::middle();
        self::assertSame('0|hzzzzz:', (string) $rank);
        self::assertSame($rank->value(), $rank->toString());
    }
}
