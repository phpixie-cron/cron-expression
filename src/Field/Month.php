<?php

declare(strict_types=1);

namespace PHPixieCron\Expression\Field;

class Month extends AbstractField
{
    protected int $lowBit = 1;
    protected int $highBit = 12;

    /**
     * @var array<int, string>
     */
    protected array $names = [1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr', 5 => 'may', 6 =>'jun', 7 => 'jul', 8 => 'aug', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec'];

    public function isSatisfiedBy(\DateTimeInterface $time): bool
    {
        return $this->bitstring->testBit((int) $time->format('m') - 1);
    }
}
