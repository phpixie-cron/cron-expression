<?php

declare(strict_types=1);

namespace PHPixieCron\Expression\Field;

use PHPixieCron\Expression\Bitstring;
use PHPixieCron\Expression\StringParser;

class DayOfMonth extends AbstractField
{
    protected int $lowBit = 1;
    protected int $highBit = 31;

    public function __construct(protected string $expression)
    {
        $this->bitstring = new Bitstring($this->lowBit, $this->highBit);
        if ($expression === '?') {
            for ($i = $this->lowBit; $i <= $this->highBit; $i++) {
                $this->bitstring->setElement($i);
            }
        } elseif (preg_match('/^(.*)W$/', $expression)) {
            // We'll have to handle isSatisfiedBy without a Bistring since this
            // value will be transient
        } elseif ($expression === 'L') {
            // We'll have to handle isSatisfiedBy without a Bistring since this
            // value will depend on the month
        } else {
            StringParser::parse($expression, $this->bitstring, $this->names);
        }
    }

    public function isSatisfiedBy(\DateTimeInterface $time): bool
    {
        // Check to see if this is the nearest weekday to a particular value
        if ($wPosition = strpos($this->expression, 'W')) {
            // Parse the target day
            $targetDay = (int) substr($this->expression, 0, $wPosition);
            // Find out if the current day is the nearest day of the week
            $next = $this->getNearestWeekday((int) $time->format('Y'), (int) $time->format('m'), $targetDay);
            if ($next) {
                return $time->format('j') === $next->format('j');
            }

            throw new \RuntimeException('Unable to find nearest weekday');
        }

        // Check to see if this is the last day of the month
        if ('L' === $this->expression) {
            return $time->format('d') === $time->format('t');
        }

        return $this->bitstring->testBit((int) $time->format('d') - 1);
    }

    /**
     * Get the nearest day of the week for a given day in a month.
     */
    protected function getNearestWeekday(int $currentYear, int $currentMonth, int $targetDay): ?\DateTime
    {
        $tday = str_pad((string) $targetDay, 2, '0', STR_PAD_LEFT);
        $target = \DateTime::createFromFormat('Y-m-d', "{$currentYear}-{$currentMonth}-{$tday}");

        if ($target === false) {
            return null;
        }

        $currentWeekday = (int) $target->format('N');

        if ($currentWeekday < 6) {
            return $target;
        }

        $lastDayOfMonth = $target->format('t');
        foreach ([-1, 1, -2, 2] as $i) {
            $adjusted = $targetDay + $i;
            if ($adjusted > 0 && $adjusted <= $lastDayOfMonth) {
                $target->setDate($currentYear, $currentMonth, $adjusted);

                if ((int) $target->format('N') < 6 && (int) $target->format('m') === $currentMonth) {
                    return $target;
                }
            }
        }

        return null;
    }
}
