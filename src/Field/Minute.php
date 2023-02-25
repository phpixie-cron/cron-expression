<?php

declare(strict_types=1);

namespace PHPixieCron\Expression\Field;

class Minute extends AbstractField
{
    protected int $lowBit = 0;
    protected int $highBit = 59;

    public function isSatisfiedBy(\DateTimeInterface $time): bool
    {
        return $this->bitstring->testBit((int) $time->format('i'));
    }
}
