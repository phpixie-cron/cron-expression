<?php

declare(strict_types=1);

namespace PHPixieCron\Expression;

/**
 * "bit" representation of a cron field
 * In cronie, individual fields are handled as bit arrays. This replicates a
 * similar structure using PHP arrays.
 *
 * @package Cron
 */
class Bitstring
{
    /**
     * @var array<int>
     */
    protected array $struct = [];

    public function __construct(protected int $lowBit, protected int $highBit)
    {
    }

    public function getLowBit(): int
    {
        return $this->lowBit;
    }

    public function getHighBit(): int
    {
        return $this->highBit;
    }

    public function testBit(int $position): bool
    {
        if (isset($this->struct[$position])) {
            return (bool) $this->struct[$position];
        }

        return false;
    }

    public function clearBit(int $position): void
    {
        $this->struct[$position] = 0;
    }

    public function setBit(int $position): void
    {
        $this->struct[$position] = 1;
    }

    public function setNBit(int $start, int $end): void
    {
        for ($i  = $start; $i <= $end; $i++) {
            $this->setBit($i);
        }
    }

    public function clearNBit(int $start, int $end): void
    {
        for ($i  = $start; $i <= $end; $i++) {
            $this->clearBit($i);
        }
    }

    public function setElement(?int $position): ?int
    {
        if ($position < $this->lowBit || $position > $this->highBit) {
            throw new \RuntimeException(sprintf("Invalid value of %d, must be between %d and %d", $position, $this->lowBit, $this->highBit));
        }

        $this->setBit(($position - $this->lowBit));
        return 0;
    }
}
