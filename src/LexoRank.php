<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank;

use MuradyanVano\LexoRank\Exception\InvalidBoundsException;
use MuradyanVano\LexoRank\Exception\InvalidRankFormatException;
use MuradyanVano\LexoRank\Exception\RankSpaceExhaustedException;
use MuradyanVano\LexoRank\Math\LexoDecimal;
use MuradyanVano\LexoRank\Math\LexoInteger;
use MuradyanVano\LexoRank\Math\NumeralSystem36;

/**
 * Immutable LexoRank value object.
 *
 * Canonical serialization: `{bucket}|{integer}{:{fraction}}` where the integer
 * part is zero-padded to 6 base-36 digits and trailing fractional zeros are
 * stripped. Example: `0|hzzzzz:`.
 *
 * Lexicographic string comparison of the canonical form matches {@see compareTo()}
 * because the alphabet is ASCII `0-9a-z` and the format is fixed-width for the
 * integer part.
 */
final class LexoRank
{
    private string $value;

    private LexoRankBucket $bucket;

    private LexoDecimal $decimal;

    private function __construct(LexoRankBucket $bucket, LexoDecimal $decimal)
    {
        $this->bucket = $bucket;
        $this->decimal = $decimal;
        $this->value = $bucket->toString() . '|' . self::formatDecimal($decimal);
    }

    public static function min(?LexoRankBucket $bucket = null): self
    {
        return new self($bucket ?? LexoRankBucket::bucket0(), self::minDecimal());
    }

    public static function max(?LexoRankBucket $bucket = null): self
    {
        return new self($bucket ?? LexoRankBucket::bucket0(), self::maxDecimal());
    }

    public static function middle(?LexoRankBucket $bucket = null): self
    {
        $bucket ??= LexoRankBucket::bucket0();

        return self::min($bucket)->between(self::max($bucket));
    }

    /**
     * Initial rank used when appending into an empty-ish list for a bucket.
     * Bucket 0 starts near the low end; buckets 1/2 start near the high end
     * (matching the Atlassian-inspired initialisation pattern).
     */
    public static function initial(?LexoRankBucket $bucket = null): self
    {
        $bucket ??= LexoRankBucket::bucket0();

        if ($bucket->equals(LexoRankBucket::bucket0())) {
            return new self($bucket, LexoDecimal::parse('100000'));
        }

        $char = NumeralSystem36::toChar(NumeralSystem36::BASE - 2);

        return new self($bucket, LexoDecimal::parse($char . '00000'));
    }

    public static function from(LexoRankBucket $bucket, LexoDecimal $decimal): self
    {
        return new self($bucket, $decimal);
    }

    public static function parse(string $rank, int $maxLength = NumeralSystem36::DEFAULT_MAX_RANK_LENGTH): self
    {
        $parsed = self::tryParse($rank, $maxLength);
        if ($parsed === null) {
            if ($rank === '') {
                throw InvalidRankFormatException::empty();
            }

            if (strlen($rank) > $maxLength) {
                throw InvalidRankFormatException::tooLong(strlen($rank), $maxLength);
            }

            if (preg_match('/\s/', $rank) === 1) {
                throw InvalidRankFormatException::whitespace($rank);
            }

            throw InvalidRankFormatException::malformed($rank);
        }

        return $parsed;
    }

    public static function tryParse(string $rank, int $maxLength = NumeralSystem36::DEFAULT_MAX_RANK_LENGTH): ?self
    {
        if ($rank === '' || strlen($rank) > $maxLength) {
            return null;
        }

        if (preg_match('/\s/', $rank) === 1) {
            return null;
        }

        $pipe = strpos($rank, '|');
        if ($pipe === false || $pipe !== 1) {
            return null;
        }

        $bucketStr = substr($rank, 0, 1);
        $decimalStr = substr($rank, 2);

        $bucket = LexoRankBucket::tryFromString($bucketStr);
        if ($bucket === null) {
            return null;
        }

        if ($decimalStr === '' || !str_contains($decimalStr, NumeralSystem36::RADIX_POINT)) {
            return null;
        }

        if (preg_match('/^[0-9a-z]*:[0-9a-z]*$/', $decimalStr) !== 1) {
            return null;
        }

        try {
            $decimal = LexoDecimal::parse($decimalStr);
        } catch (\Throwable) {
            return null;
        }

        $candidate = new self($bucket, $decimal);

        // Reject non-canonical serializations so round-trips are stable.
        if ($candidate->value !== $rank) {
            // Still accept semantically valid ranks that differ only by
            // unpadded integer width / trailing fractional zeros by returning
            // the canonical form.
            return $candidate;
        }

        return $candidate;
    }

    public function between(self $other): self
    {
        if (!$this->bucket->equals($other->bucket)) {
            throw InvalidBoundsException::differentBuckets(
                $this->bucket->toString(),
                $other->bucket->toString(),
            );
        }

        $cmp = $this->decimal->compareTo($other->decimal);
        if ($cmp === 0) {
            throw InvalidBoundsException::equal();
        }

        if ($cmp > 0) {
            $mid = self::betweenDecimals($other->decimal, $this->decimal);
        } else {
            $mid = self::betweenDecimals($this->decimal, $other->decimal);
        }

        return new self($this->bucket, $mid);
    }

    /**
     * Generate a rank strictly before this rank (closer to min).
     */
    public function before(): self
    {
        if ($this->isMin()) {
            throw RankSpaceExhaustedException::beforeMin($this->value);
        }

        if ($this->isMax()) {
            return new self($this->bucket, self::initialMaxDecimal());
        }

        $floor = LexoDecimal::fromInteger($this->decimal->floor());
        $step = LexoDecimal::parse((string) NumeralSystem36::STEP);
        $next = $floor->subtract($step);

        if ($next->compareTo(self::minDecimal()) <= 0) {
            $next = self::betweenDecimals(self::minDecimal(), $this->decimal);
        }

        return new self($this->bucket, $next);
    }

    /**
     * Generate a rank strictly after this rank (closer to max).
     */
    public function after(): self
    {
        if ($this->isMax()) {
            throw RankSpaceExhaustedException::afterMax($this->value);
        }

        if ($this->isMin()) {
            return new self($this->bucket, LexoDecimal::parse('100000'));
        }

        $ceil = LexoDecimal::fromInteger($this->decimal->ceil());
        $step = LexoDecimal::parse((string) NumeralSystem36::STEP);
        $next = $ceil->add($step);

        if ($next->compareTo(self::maxDecimal()) >= 0) {
            $next = self::betweenDecimals($this->decimal, self::maxDecimal());
        }

        return new self($this->bucket, $next);
    }

    public function inNextBucket(): self
    {
        return new self($this->bucket->next(), $this->decimal);
    }

    public function inPreviousBucket(): self
    {
        return new self($this->bucket->previous(), $this->decimal);
    }

    public function withBucket(LexoRankBucket $bucket): self
    {
        return new self($bucket, $this->decimal);
    }

    public function compareTo(self $other): int
    {
        return strcmp($this->value, $other->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isBefore(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function isAfter(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function isMin(): bool
    {
        return $this->decimal->equals(self::minDecimal());
    }

    public function isMax(): bool
    {
        return $this->decimal->equals(self::maxDecimal());
    }

    public function bucket(): LexoRankBucket
    {
        return $this->bucket;
    }

    /**
     * Canonical serialized rank string.
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * @internal
     */
    public function decimal(): LexoDecimal
    {
        return $this->decimal;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function length(): int
    {
        return strlen($this->value);
    }

    /**
     * Midpoint between two ordered decimals (left < right).
     *
     * @internal
     */
    public static function betweenDecimals(LexoDecimal $left, LexoDecimal $right): LexoDecimal
    {
        $oLeft = $left;
        $oRight = $right;

        if ($left->scale() < $right->scale()) {
            $nLeft = $right->setScale($left->scale(), false);
            if ($left->compareTo($nLeft) >= 0) {
                return self::mid($left, $right);
            }
            $right = $nLeft;
        }

        if ($left->scale() > $right->scale()) {
            $nLeft = $left->setScale($right->scale(), true);
            if ($nLeft->compareTo($right) >= 0) {
                return self::mid($left, $right);
            }
            $left = $nLeft;
        }

        for ($scale = $left->scale(); $scale > 0; $right = $nRight) {
            $nScale = $scale - 1;
            $nLeft = $left->setScale($nScale, true);
            $nRight = $right->setScale($nScale, false);
            $cmp = $nLeft->compareTo($nRight);
            if ($cmp === 0) {
                return self::checkMid($oLeft, $oRight, $nLeft);
            }
            if ($nLeft->compareTo($nRight) > 0) {
                break;
            }
            $scale = $nScale;
            $left = $nLeft;
        }

        $mid = self::checkMid($oLeft, $oRight, self::mid($left, $right));

        for ($mScale = $mid->scale(); $mScale > 0; $mScale = $nScale) {
            $nScale = $mScale - 1;
            $nMid = $mid->setScale($nScale);
            if ($oLeft->compareTo($nMid) >= 0 || $nMid->compareTo($oRight) >= 0) {
                break;
            }
            $mid = $nMid;
        }

        return $mid;
    }

    private static function mid(LexoDecimal $left, LexoDecimal $right): LexoDecimal
    {
        $sum = $left->add($right);
        $mid = $sum->multiply(LexoDecimal::half());
        $scale = max($left->scale(), $right->scale());

        if ($mid->scale() > $scale) {
            $roundDown = $mid->setScale($scale, false);
            if ($roundDown->compareTo($left) > 0) {
                return $roundDown;
            }
            $roundUp = $mid->setScale($scale, true);
            if ($roundUp->compareTo($right) < 0) {
                return $roundUp;
            }
        }

        return $mid;
    }

    private static function checkMid(LexoDecimal $lbound, LexoDecimal $rbound, LexoDecimal $mid): LexoDecimal
    {
        if ($lbound->compareTo($mid) >= 0) {
            return self::mid($lbound, $rbound);
        }

        if ($mid->compareTo($rbound) >= 0) {
            return self::mid($lbound, $rbound);
        }

        return $mid;
    }

    private static function formatDecimal(LexoDecimal $decimal): string
    {
        $formatVal = $decimal->format();
        $radix = NumeralSystem36::RADIX_POINT;
        $partialIndex = strpos($formatVal, $radix);

        if ($partialIndex === false) {
            $formatVal .= $radix;
            $partialIndex = strlen($formatVal) - 1;
        }

        while ($partialIndex < NumeralSystem36::INTEGER_WIDTH) {
            $formatVal = '0' . $formatVal;
            ++$partialIndex;
        }

        while (str_ends_with($formatVal, '0')) {
            $formatVal = substr($formatVal, 0, -1);
        }

        return $formatVal;
    }

    private static function minDecimal(): LexoDecimal
    {
        static $min = null;
        if ($min === null) {
            $min = LexoDecimal::fromInteger(LexoInteger::zero());
        }

        return $min;
    }

    private static function maxDecimal(): LexoDecimal
    {
        static $max = null;
        if ($max === null) {
            $max = LexoDecimal::parse('1000000')->subtract(LexoDecimal::parse('1'));
        }

        return $max;
    }

    private static function initialMaxDecimal(): LexoDecimal
    {
        static $value = null;
        if ($value === null) {
            $char = NumeralSystem36::toChar(NumeralSystem36::BASE - 2);
            $value = LexoDecimal::parse($char . '00000');
        }

        return $value;
    }
}
