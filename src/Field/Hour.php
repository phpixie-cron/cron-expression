<?php

declare(strict_types=1);

namespace PHPixieCron\Expression\Field;

class Hour extends AbstractField
{
    protected int $lowBit = 0;
    protected int $highBit = 23;

    public function isSatisfiedBy(\DateTimeInterface $time): bool
    {
        return $this->bitstring->testBit((int) $time->format('H'));
    }
}
