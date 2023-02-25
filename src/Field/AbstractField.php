<?php

declare(strict_types=1);

namespace PHPixieCron\Expression\Field;

use PHPixieCron\Expression\Bitstring;
use PHPixieCron\Expression\StringParser;

abstract class AbstractField
{
    protected int $lowBit;
    protected int $highBit;

    protected Bitstring $bitstring;

    /**
     * @var array<int, string>
     */
    protected array $names = [];

    public function __construct(protected string $expression)
    {
        $this->bitstring = new Bitstring($this->lowBit, $this->highBit);
        StringParser::parse($expression, $this->bitstring, $this->names);
    }

    public function convertLiteral(string $literal): int
    {
        if (is_numeric($literal)) {
            return (int) $literal;
        } else {
            $bitstring = new Bitstring($this->lowBit, $this->highBit);
            StringParser::parse($literal, $bitstring, $this->names);
            for ($i = $this->lowBit; $i <= $this->highBit; $i++) {
                if ($bitstring->testBit($i)) {
                    return $i;
                }
            }
        }

        throw new \InvalidArgumentException('Invalid literal of ' . $literal);
    }

    abstract public function isSatisfiedBy(\DateTimeInterface $time): bool;

    /**
     * Passthrough, should get rid of
     */
    public function testBit(int $position): bool
    {
        return $this->bitstring->testBit($position);
    }

    public function setBit(int $position): void
    {
        $this->bitstring->setBit($position);
    }
}
