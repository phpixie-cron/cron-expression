<?php

declare(strict_types=1);

namespace PHPixieCron\Expression;

/**
 * Parses a cron expression into a bitstring
 *
 * @package Cron
 */
final class StringParser
{
    public const R_START = 1;
    public const R_AST = 2;
    public const R_RANDOM = 3;
    public const R_NUM1 = 4;
    public const R_STEP = 5;
    public const R_FINISH = 6;
    public const R_TERMS = 7;
    public const R_RANGE = 8;
    public const R_RANGE_NUM2 = 9;

    /**
     * @param array<int, string> $names
     */
    protected function __construct(
        protected StringReader $stringReader,
        protected Bitstring $bitstring,
        protected array $names = [],
    ) {
    }

    /**
     * @param array<int, string> $names
     */
    public static function parse(string $expression, Bitstring $bitstring, array $names = []): void
    {
        $parser = new static(new StringReader($expression . "\n"), $bitstring, $names);
        try {
            $parser->getList();
        } catch (\Exception $e) {
            throw new \Exception('Invalid value of ' . $expression);
        }
    }

    protected function getList(): void
    {
        $done = false;
        $this->bitstring->clearNBit(0, ($this->bitstring->getHighBit() - $this->bitstring->getLowBit()));
        while (!$done) {
            $ch = $this->getRange();
            if ($ch === ',') {
                continue;
            } else {
                $done = true;
            }
        }

        if (is_null($ch)) {
            throw new \RuntimeException('Invalid value');
        }
    }

    protected function isSeparator(string $ch): bool
    {
        switch ($ch) {
            case "\t":
            case "\n":
            case ' ':
            case ',':
                return true;
            default:
                return false;
        }
    }

    protected function getRange(): ?string
    {
        $ch = $i = $num1 = $num2 = $num3 = null;
        $num3 = 1;
        $state = static::R_START;
        while ($state != static::R_FINISH && (($ch = $this->stringReader->getChar())) !== null) {
            switch ($state) {
                case static::R_START:
                    if ($ch === '*') {
                        $num1 = $this->bitstring->getLowBit();
                        $num2 = $this->bitstring->getHighBit();
                        $state = static::R_AST;
                        break;
                    }

                    if ($ch === '~') {
                        $num1 = $this->bitstring->getLowBit();
                        $state = static::R_RANDOM;
                        break;
                    }

                    if ($this->getNumber($num1) !== null) {
                        $state = static::R_NUM1;
                        break;
                    }

                    return null;
                case static::R_AST:
                    if ($ch === '/') {
                        $state = static::R_STEP;
                        break;
                    }

                    if ($this->isSeparator($ch)) {
                        $state = static::R_FINISH;
                        break;
                    }
                    return null;
                case static::R_STEP:
                    if ($this->getNumber($num3) === 0) {
                        $state = static::R_TERMS;
                        break;
                    }
                    return null;
                case static::R_TERMS:
                    if ($this->isSeparator($ch)) {
                        $state = static::R_FINISH;
                        break;
                    }
                    return null;
                case static::R_NUM1:
                    if ($ch === '-') {
                        $state = static::R_RANGE;
                        break;
                    }
                    if ($ch === '~') {
                        $state = static::R_RANDOM;
                        break;
                    }
                    if ($this->isSeparator($ch)) {
                        $num2 = $num1;
                        $state = static::R_FINISH;
                        break;
                    }
                    return null;
                case static::R_RANGE:
                    if ($this->getNumber($num2) !== null) {
                        $state = static::R_RANGE_NUM2;
                        break;
                    }
                    return null;
                case static::R_RANGE_NUM2:
                    if ($ch === '/') {
                        $state = static::R_STEP;
                        break;
                    }
                    if ($this->isSeparator($ch)) {
                        $state = static::R_FINISH;
                        break;
                    }
                    return null;
                case static::R_RANDOM:
                    if ($this->isSeparator($ch)) {
                        $num2 = $this->bitstring->getHighBit();
                        $state = static::R_FINISH;
                    } else {
                        $this->stringReader->ungetChar();
                        if ($this->getNumber($num2) !== null) {
                            $state = static::R_TERMS;
                        } else {
                            return null;
                        }
                    }
                    if ($num1 > $num2) {
                        return null;
                    }

                    $num1 = $num2 = rand($this->bitstring->getLowBit(), $this->bitstring->getHighBit());
                    break;
                default:
                    return null;
            }
        }

        if ($state !== static::R_FINISH || $ch == null) {
            return null;
        }

        if ($num1 > $num2) {
            return null;
        }

        for ($i = $num1; $i <= $num2; $i += $num3) {
            if ($this->bitstring->setElement($i) === null) {
                $this->stringReader->ungetChar();
                return null;
            }
        }

        return $ch;
    }

    // Returns 0 as in "no error occurred", otherwise it returned EOF (which we fake with null)
    protected function getNumber(?int &$num): ?int
    {
        $len = 0;
        $temp = '';
        $this->stringReader->ungetChar();
        while ($this->isAlphaNumeric(($ch = $this->stringReader->getChar()))) {
            $len++;
            $temp .= $ch;
        }
        if ($len === 0) {
            $this->stringReader->ungetChar();
            return null;
        }

        $this->stringReader->ungetChar();

        if (is_numeric($temp)) {
            $num = (int) $temp;
            return 0;
        }

        if ($this->names) {
            foreach ($this->names as $id => $name) {
                if (strtolower($name) === strtolower($temp)) {
                    $num = $id;
                    return 0;
                }
            }
        }

        return null;
    }

    protected function isAlphaNumeric(?string $char): bool
    {
        return ctype_alnum($char);
    }
}
