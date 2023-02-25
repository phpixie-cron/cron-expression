<?php

declare(strict_types=1);

namespace PHPixieCron\Expression;

/**
 * Helper class for reading a string character-by-character
 *
 * @package Cron
 */
class StringReader
{
    protected int $slot = 0;

    public function __construct(protected string $string)
    {
        $this->string = $this->string . "\n";
    }

    public function getChar(): ?string
    {
        if (isset($this->string[$this->slot])) {
            $char = $this->string[$this->slot];
            $this->slot++;
            return $char;
        }

        return null;
    }

    public function ungetChar(): void
    {
        $this->slot--;
    }
}
