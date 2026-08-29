<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Math;

use MuradyanVano\LexoRank\Exception\LexoRankException;

/**
 * Arbitrary-precision signed integer in base 36 using little-endian digit arrays.
 *
 * Digits are stored least-significant first so carry/borrow run left-to-right.
 * This class is an internal implementation detail and must not be used by
 * application code.
 *
 * @internal
 */
final class LexoInteger
{
    private const SIGN_NEGATIVE = -1;

    private const SIGN_ZERO = 0;

    private const SIGN_POSITIVE = 1;

    /** @var list<int> */
    private array $mag;

    private int $sign;

    /**
     * @param list<int> $mag little-endian digits in 0..base-1
     */
    private function __construct(int $sign, array $mag)
    {
        $this->sign = $sign;
        $this->mag = $mag;
    }

    public static function zero(): self
    {
        return new self(self::SIGN_ZERO, [0]);
    }

    public static function one(): self
    {
        return new self(self::SIGN_POSITIVE, [1]);
    }

    /**
     * @param list<int> $mag
     */
    public static function make(int $sign, array $mag): self
    {
        $length = count($mag);
        while ($length > 0 && $mag[$length - 1] === 0) {
            --$length;
        }

        if ($length === 0) {
            return self::zero();
        }

        if ($length !== count($mag)) {
            $mag = array_slice($mag, 0, $length);
        }

        /** @var list<int> $mag */
        return new self($sign, $mag);
    }

    public static function parse(string $value): self
    {
        if ($value === '') {
            throw new LexoRankException('Integer string must not be empty.');
        }

        $sign = self::SIGN_POSITIVE;
        $offset = 0;

        if ($value[0] === NumeralSystem36::POSITIVE_CHAR) {
            $offset = 1;
        } elseif ($value[0] === NumeralSystem36::NEGATIVE_CHAR) {
            $sign = self::SIGN_NEGATIVE;
            $offset = 1;
        }

        $digits = substr($value, $offset);
        if ($digits === '') {
            throw new LexoRankException('Integer string has no digits.');
        }

        $length = strlen($digits);
        $mag = [];

        for ($i = $length - 1; $i >= 0; --$i) {
            $mag[] = NumeralSystem36::toDigit($digits[$i]);
        }

        return self::make($sign, $mag);
    }

    public function isZero(): bool
    {
        return $this->sign === self::SIGN_ZERO;
    }

    public function isOne(): bool
    {
        return $this->sign === self::SIGN_POSITIVE
            && count($this->mag) === 1
            && $this->mag[0] === 1;
    }

    public function getMag(int $index): int
    {
        return $this->mag[$index] ?? 0;
    }

    /**
     * @return list<int>
     */
    public function mag(): array
    {
        return $this->mag;
    }

    public function sign(): int
    {
        return $this->sign;
    }

    public function digitCount(): int
    {
        return count($this->mag);
    }

    public function add(self $other): self
    {
        if ($this->isZero()) {
            return $other;
        }

        if ($other->isZero()) {
            return $this;
        }

        if ($this->sign !== $other->sign) {
            if ($this->sign === self::SIGN_NEGATIVE) {
                return $other->subtract($this->negate());
            }

            return $this->subtract($other->negate());
        }

        return self::make($this->sign, self::addMags($this->mag, $other->mag));
    }

    public function subtract(self $other): self
    {
        if ($this->isZero()) {
            return $other->negate();
        }

        if ($other->isZero()) {
            return $this;
        }

        if ($this->sign !== $other->sign) {
            return $this->add($other->negate());
        }

        $cmp = self::compareMags($this->mag, $other->mag);
        if ($cmp === 0) {
            return self::zero();
        }

        if ($cmp < 0) {
            $resultSign = $this->sign === self::SIGN_NEGATIVE
                ? self::SIGN_POSITIVE
                : self::SIGN_NEGATIVE;

            return self::make($resultSign, self::subtractMags($other->mag, $this->mag));
        }

        $resultSign = $this->sign === self::SIGN_NEGATIVE
            ? self::SIGN_NEGATIVE
            : self::SIGN_POSITIVE;

        return self::make($resultSign, self::subtractMags($this->mag, $other->mag));
    }

    public function multiply(self $other): self
    {
        if ($this->isZero() || $other->isZero()) {
            return self::zero();
        }

        if ($this->isOneish()) {
            return self::make(
                $this->sign === $other->sign ? self::SIGN_POSITIVE : self::SIGN_NEGATIVE,
                $other->mag,
            );
        }

        if ($other->isOneish()) {
            return self::make(
                $this->sign === $other->sign ? self::SIGN_POSITIVE : self::SIGN_NEGATIVE,
                $this->mag,
            );
        }

        $product = self::multiplyMags($this->mag, $other->mag);

        return self::make(
            $this->sign === $other->sign ? self::SIGN_POSITIVE : self::SIGN_NEGATIVE,
            $product,
        );
    }

    public function multiplyBySmall(int $factor): self
    {
        if ($factor === 0 || $this->isZero()) {
            return self::zero();
        }

        if ($factor < 0) {
            throw new LexoRankException('Small multiplication factor must be non-negative.');
        }

        if ($factor === 1) {
            return $this;
        }

        $base = NumeralSystem36::BASE;
        $result = [];
        $carry = 0;

        foreach ($this->mag as $digit) {
            $value = ($digit * $factor) + $carry;
            $result[] = $value % $base;
            $carry = intdiv($value, $base);
        }

        while ($carry > 0) {
            $result[] = $carry % $base;
            $carry = intdiv($carry, $base);
        }

        return self::make($this->sign, $result);
    }

    public function negate(): self
    {
        if ($this->isZero()) {
            return $this;
        }

        return self::make(
            $this->sign === self::SIGN_POSITIVE ? self::SIGN_NEGATIVE : self::SIGN_POSITIVE,
            $this->mag,
        );
    }

    public function shiftLeft(int $times = 1): self
    {
        if ($times === 0 || $this->isZero()) {
            return $this;
        }

        if ($times < 0) {
            return $this->shiftRight(abs($times));
        }

        $nmag = array_fill(0, $times, 0);
        foreach ($this->mag as $digit) {
            $nmag[] = $digit;
        }

        /** @var list<int> $nmag */
        return self::make($this->sign, $nmag);
    }

    public function shiftRight(int $times = 1): self
    {
        if ($times === 0) {
            return $this;
        }

        if ($times < 0) {
            return $this->shiftLeft(abs($times));
        }

        if (count($this->mag) <= $times) {
            return self::zero();
        }

        /** @var list<int> $nmag */
        $nmag = array_slice($this->mag, $times);

        return self::make($this->sign, $nmag);
    }

    public function compareTo(self $other): int
    {
        if ($this->sign < $other->sign) {
            return -1;
        }

        if ($this->sign > $other->sign) {
            return 1;
        }

        if ($this->sign === self::SIGN_ZERO) {
            return 0;
        }

        $cmp = self::compareMags($this->mag, $other->mag);

        if ($this->sign === self::SIGN_NEGATIVE) {
            return -$cmp;
        }

        return $cmp;
    }

    public function equals(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function format(): string
    {
        if ($this->isZero()) {
            return '0';
        }

        $chars = [];
        for ($i = count($this->mag) - 1; $i >= 0; --$i) {
            $chars[] = NumeralSystem36::toChar($this->mag[$i]);
        }

        $value = implode('', $chars);

        if ($this->sign === self::SIGN_NEGATIVE) {
            return NumeralSystem36::NEGATIVE_CHAR . $value;
        }

        return $value;
    }

    private function isOneish(): bool
    {
        return count($this->mag) === 1 && $this->mag[0] === 1;
    }

    /**
     * @param list<int> $left
     * @param list<int> $right
     *
     * @return list<int>
     */
    private static function addMags(array $left, array $right): array
    {
        $base = NumeralSystem36::BASE;
        $size = max(count($left), count($right));
        $result = [];
        $carry = 0;

        for ($i = 0; $i < $size; ++$i) {
            $sum = ($left[$i] ?? 0) + ($right[$i] ?? 0) + $carry;
            $carry = intdiv($sum, $base);
            $result[$i] = $sum % $base;
        }

        if ($carry > 0) {
            $result[] = $carry;
        }

        return $result;
    }

    /**
     * @param list<int> $left  minuend (must be >= right)
     * @param list<int> $right subtrahend
     *
     * @return list<int>
     */
    private static function subtractMags(array $left, array $right): array
    {
        $base = NumeralSystem36::BASE;
        $size = count($left);
        $result = [];
        $borrow = 0;

        for ($i = 0; $i < $size; ++$i) {
            $diff = ($left[$i] ?? 0) - ($right[$i] ?? 0) - $borrow;
            if ($diff < 0) {
                $diff += $base;
                $borrow = 1;
            } else {
                $borrow = 0;
            }
            $result[$i] = $diff;
        }

        if ($borrow !== 0) {
            throw new LexoRankException('Internal borrow error during subtraction.');
        }

        /** @var list<int> $result */
        return $result;
    }

    /**
     * @param list<int> $left
     * @param list<int> $right
     *
     * @return list<int>
     */
    private static function multiplyMags(array $left, array $right): array
    {
        $base = NumeralSystem36::BASE;
        $result = array_fill(0, count($left) + count($right), 0);

        foreach ($left as $li => $lDigit) {
            foreach ($right as $ri => $rDigit) {
                $index = $li + $ri;
                $result[$index] += $lDigit * $rDigit;
                while ($result[$index] >= $base) {
                    $result[$index] -= $base;
                    ++$result[$index + 1];
                }
            }
        }

        /** @var list<int> $result */
        return $result;
    }

    /**
     * @param list<int> $left
     * @param list<int> $right
     */
    private static function compareMags(array $left, array $right): int
    {
        $leftLen = count($left);
        $rightLen = count($right);

        if ($leftLen < $rightLen) {
            return -1;
        }

        if ($leftLen > $rightLen) {
            return 1;
        }

        for ($i = $leftLen - 1; $i >= 0; --$i) {
            if ($left[$i] < $right[$i]) {
                return -1;
            }
            if ($left[$i] > $right[$i]) {
                return 1;
            }
        }

        return 0;
    }
}
