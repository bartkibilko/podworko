<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;

/**
 * Immutable, integer-backed money in grosze (1 PLN = 100 grosze).
 *
 * All monetary arithmetic goes through this type — no other code does money
 * maths (coding-rules § Money). Negative values are allowed (net positions).
 * The FR-010 rounding/distribution policy lives in the settlement allocator
 * (S-03), not here: divide() only returns an equal base share + remainder.
 */
final class Money
{
    public function __construct(private readonly int $grosze) {}

    public function grosze(): int
    {
        return $this->grosze;
    }

    public function add(self $other): self
    {
        return new self($this->grosze + $other->grosze);
    }

    public function subtract(self $other): self
    {
        return new self($this->grosze - $other->grosze);
    }

    public function multiply(int $factor): self
    {
        return new self($this->grosze * $factor);
    }

    /**
     * Split into an equal base share plus the leftover remainder, such that
     * `baseShare * parts + remainder === grosze`.
     *
     * @return array{0: int, 1: int} [baseShare, remainder]
     */
    public function divide(int $parts): array
    {
        if ($parts <= 0) {
            throw new InvalidArgumentException("Cannot divide Money into {$parts} parts.");
        }

        $baseShare = intdiv($this->grosze, $parts);
        $remainder = $this->grosze - $baseShare * $parts;

        return [$baseShare, $remainder];
    }

    public function equals(self $other): bool
    {
        return $this->grosze === $other->grosze;
    }

    public function isNegative(): bool
    {
        return $this->grosze < 0;
    }

    /** Display string, e.g. 1234 → "12,34 zł", -1234 → "-12,34 zł". */
    public function format(): string
    {
        $sign = $this->grosze < 0 ? '-' : '';
        $abs = abs($this->grosze);

        return sprintf('%s%d,%02d zł', $sign, intdiv($abs, 100), $abs % 100);
    }
}
