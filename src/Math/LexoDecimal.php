<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Math;

use MuradyanVano\LexoRank\Exception\LexoRankException;

/**
 * Fixed-point base-36 decimal used by LexoRank.
 *
 * Internally stored as an integer magnitude plus a scale (number of digits
 * after the radix point). Trailing fractional zeros are normalized away.
 *
 * @internal
 */
final class LexoDecimal
{
    private LexoInteger $mag;

    private int $scale;

    private function __construct(LexoInteger $mag, int $scale)
    {
        $this->mag = $mag;
        $this->scale = $scale;
    }

    public static function half(): self
    {
        $mid = intdiv(NumeralSystem36::BASE, 2);

        return self::make(LexoInteger::make(1, [$mid]), 1);
    }

    public static function fromInteger(LexoInteger $integer): self
    {
        return self::make($integer, 0);
    }

    public static function parse(string $value): self
    {
        if ($value === '') {
            throw new LexoRankException('Decimal string must not be empty.');
        }

        $radix = NumeralSystem36::RADIX_POINT;
        $first = strpos($value, $radix);
        $last = strrpos($value, $radix);

        if ($first !== false && $first !== $last) {
            throw new LexoRankException(sprintf(
                'Decimal string "%s" contains more than one radix point.',
                $value,
            ));
        }

        if ($first === false) {
            return self::make(LexoInteger::parse($value), 0);
        }

        $intPart = substr($value, 0, $first);
        $fracPart = substr($value, $first + 1);
        $combined = $intPart . $fracPart;

        if ($combined === '' || $combined === NumeralSystem36::POSITIVE_CHAR || $combined === NumeralSystem36::NEGATIVE_CHAR) {
            throw new LexoRankException(sprintf('Invalid decimal string "%s".', $value));
        }

        $scale = strlen($fracPart);

        return self::make(LexoInteger::parse($combined), $scale);
    }

    public static function make(LexoInteger $integer, int $scale): self
    {
        if ($scale < 0) {
            throw new LexoRankException('Scale must be non-negative.');
        }

        if ($integer->isZero()) {
            return new self($integer, 0);
        }

        $zeroCount = 0;
        while ($zeroCount < $scale && $integer->getMag($zeroCount) === 0) {
            ++$zeroCount;
        }

        if ($zeroCount === 0) {
            return new self($integer, $scale);
        }

        return new self($integer->shiftRight($zeroCount), $scale - $zeroCount);
    }

    public function mag(): LexoInteger
    {
        return $this->mag;
    }

    public function scale(): int
    {
        return $this->scale;
    }

    public function add(self $other): self
    {
        [$leftMag, $rightMag, $scale] = $this->align($other);

        return self::make($leftMag->add($rightMag), $scale);
    }

    public function subtract(self $other): self
    {
        [$leftMag, $rightMag, $scale] = $this->align($other);

        return self::make($leftMag->subtract($rightMag), $scale);
    }

    public function multiply(self $other): self
    {
        return self::make(
            $this->mag->multiply($other->mag),
            $this->scale + $other->scale,
        );
    }

    public function floor(): LexoInteger
    {
        return $this->mag->shiftRight($this->scale);
    }

    public function ceil(): LexoInteger
    {
        if ($this->isExact()) {
            return $this->mag->shiftRight($this->scale);
        }

        return $this->floor()->add(LexoInteger::one());
    }

    public function isExact(): bool
    {
        if ($this->scale === 0) {
            return true;
        }

        for ($i = 0; $i < $this->scale; ++$i) {
            if ($this->mag->getMag($i) !== 0) {
                return false;
            }
        }

        return true;
    }

    public function setScale(int $newScale, bool $ceiling = false): self
    {
        if ($newScale >= $this->scale) {
            return $this;
        }

        if ($newScale < 0) {
            $newScale = 0;
        }

        $diff = $this->scale - $newScale;
        $nmag = $this->mag->shiftRight($diff);

        if ($ceiling) {
            $nmag = $nmag->add(LexoInteger::one());
        }

        return self::make($nmag, $newScale);
    }

    public function compareTo(self $other): int
    {
        if ($this->scale > $other->scale) {
            $otherMag = $other->mag->shiftLeft($this->scale - $other->scale);

            return $this->mag->compareTo($otherMag);
        }

        if ($this->scale < $other->scale) {
            $thisMag = $this->mag->shiftLeft($other->scale - $this->scale);

            return $thisMag->compareTo($other->mag);
        }

        return $this->mag->compareTo($other->mag);
    }

    public function equals(self $other): bool
    {
        return $this->scale === $other->scale && $this->mag->equals($other->mag);
    }

    public function format(): string
    {
        $intStr = $this->mag->format();

        if ($this->scale === 0) {
            return $intStr;
        }

        $negative = false;
        if ($intStr[0] === NumeralSystem36::NEGATIVE_CHAR) {
            $negative = true;
            $intStr = substr($intStr, 1);
        } elseif ($intStr[0] === NumeralSystem36::POSITIVE_CHAR) {
            $intStr = substr($intStr, 1);
        }

        while (strlen($intStr) < $this->scale + 1) {
            $intStr = '0' . $intStr;
        }

        $split = strlen($intStr) - $this->scale;
        $formatted = substr($intStr, 0, $split) . NumeralSystem36::RADIX_POINT . substr($intStr, $split);

        if ($split === 0) {
            $formatted = '0' . $formatted;
        }

        if ($negative) {
            return NumeralSystem36::NEGATIVE_CHAR . $formatted;
        }

        return $formatted;
    }

    /**
     * @return array{0: LexoInteger, 1: LexoInteger, 2: int}
     */
    private function align(self $other): array
    {
        $leftMag = $this->mag;
        $leftScale = $this->scale;
        $rightMag = $other->mag;
        $rightScale = $other->scale;

        while ($leftScale < $rightScale) {
            $leftMag = $leftMag->shiftLeft();
            ++$leftScale;
        }

        while ($rightScale < $leftScale) {
            $rightMag = $rightMag->shiftLeft();
            ++$rightScale;
        }

        return [$leftMag, $rightMag, $leftScale];
    }
}
