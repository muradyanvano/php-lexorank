<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank;

use MuradyanVano\LexoRank\Exception\InvalidBucketException;

/**
 * Rotating LexoRank bucket (0 → 1 → 2 → 0).
 *
 * Buckets enable rebalancing without disturbing lexicographic order during
 * migration: new ranks are written into the next bucket while old ranks remain
 * readable until the transaction commits.
 */
final class LexoRankBucket
{
    private const VALUES = ['0', '1', '2'];

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function bucket0(): self
    {
        return new self('0');
    }

    public static function bucket1(): self
    {
        return new self('1');
    }

    public static function bucket2(): self
    {
        return new self('2');
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return [
            self::bucket0(),
            self::bucket1(),
            self::bucket2(),
        ];
    }

    public static function fromString(string $value): self
    {
        if (!in_array($value, self::VALUES, true)) {
            throw InvalidBucketException::unknown($value);
        }

        return new self($value);
    }

    public static function fromId(int $id): self
    {
        if ($id < 0 || $id > 2) {
            throw InvalidBucketException::unknownId($id);
        }

        return new self((string) $id);
    }

    public static function tryFromString(string $value): ?self
    {
        if (!in_array($value, self::VALUES, true)) {
            return null;
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function id(): int
    {
        return (int) $this->value;
    }

    public function next(): self
    {
        return match ($this->value) {
            '0' => self::bucket1(),
            '1' => self::bucket2(),
            default => self::bucket0(),
        };
    }

    public function previous(): self
    {
        return match ($this->value) {
            '0' => self::bucket2(),
            '1' => self::bucket0(),
            default => self::bucket1(),
        };
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function compareTo(self $other): int
    {
        return $this->value <=> $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
