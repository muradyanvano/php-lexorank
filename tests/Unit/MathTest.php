<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Tests\Unit;

use MuradyanVano\LexoRank\Exception\LexoRankException;
use MuradyanVano\LexoRank\Math\LexoDecimal;
use MuradyanVano\LexoRank\Math\LexoInteger;
use MuradyanVano\LexoRank\Math\NumeralSystem36;
use PHPUnit\Framework\TestCase;

final class MathTest extends TestCase
{
    public function test_integer_add_with_carry(): void
    {
        $a = LexoInteger::parse('z');
        $b = LexoInteger::parse('1');
        self::assertSame('10', $a->add($b)->format());
    }

    public function test_integer_subtract_with_borrow(): void
    {
        $a = LexoInteger::parse('10');
        $b = LexoInteger::parse('1');
        self::assertSame('z', $a->subtract($b)->format());
    }

    public function test_integer_leading_zeros_normalized(): void
    {
        $a = LexoInteger::make(1, [1, 0, 0]);
        self::assertSame('1', $a->format());
        self::assertSame(1, $a->digitCount());
    }

    public function test_integer_compare(): void
    {
        $a = LexoInteger::parse('10');
        $b = LexoInteger::parse('z');
        self::assertSame(1, $a->compareTo($b));
        self::assertSame(-1, $b->compareTo($a));
        self::assertSame(0, $a->compareTo(LexoInteger::parse('10')));
    }

    public function test_integer_multiply(): void
    {
        $a = LexoInteger::parse('10');
        $b = LexoInteger::parse('10');
        self::assertSame('100', $a->multiply($b)->format());
    }

    public function test_integer_shift(): void
    {
        $a = LexoInteger::parse('1');
        self::assertSame('10', $a->shiftLeft()->format());
        self::assertSame('0', $a->shiftLeft()->shiftRight()->shiftRight()->format());
    }

    public function test_integer_negative(): void
    {
        $a = LexoInteger::parse('-10');
        $b = LexoInteger::parse('1');
        self::assertSame('-z', $a->add($b)->format());
        self::assertTrue($a->compareTo($b) < 0);
    }

    public function test_decimal_midpoint_half(): void
    {
        $half = LexoDecimal::half();
        self::assertSame(1, $half->scale());
        self::assertSame(18, $half->mag()->getMag(0));
    }

    public function test_decimal_add_different_scales(): void
    {
        $a = LexoDecimal::parse('1:0');
        $b = LexoDecimal::parse('0:1');
        // 1.0 + 0.1 in base36 — after normalize trailing zeros on 1:0 → scale 0 value 1
        $sum = $a->add($b);
        self::assertSame(1, $sum->compareTo($a));
    }

    public function test_decimal_trailing_zero_normalization(): void
    {
        $a = LexoDecimal::parse('1:00');
        self::assertSame(0, $a->scale());
        self::assertSame('1', $a->format());
    }

    public function test_decimal_floor_ceil(): void
    {
        $a = LexoDecimal::parse('1:i');
        self::assertSame('1', $a->floor()->format());
        self::assertSame('2', $a->ceil()->format());
        self::assertSame('1', LexoDecimal::parse('1')->ceil()->format());
    }

    public function test_invalid_digit(): void
    {
        $this->expectException(LexoRankException::class);
        NumeralSystem36::toDigit('A');
    }

    public function test_numeral_round_trip(): void
    {
        for ($i = 0; $i < 36; ++$i) {
            $char = NumeralSystem36::toChar($i);
            self::assertSame($i, NumeralSystem36::toDigit($char));
        }
    }

    public function test_large_value_operations(): void
    {
        $a = LexoInteger::parse('zzzzzzzzzz');
        $b = LexoInteger::parse('1');
        $sum = $a->add($b);
        self::assertSame('10000000000', $sum->format());
        self::assertSame('zzzzzzzzzz', $sum->subtract($b)->format());
    }

    public function test_zero_values(): void
    {
        $zero = LexoInteger::zero();
        self::assertTrue($zero->isZero());
        self::assertSame('0', $zero->add(LexoInteger::one())->subtract(LexoInteger::one())->format());
    }
}
