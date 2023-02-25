<?php

declare(strict_types=1);

namespace PHPixieCron\Expression\Field;

use PHPixieCron\Expression\Bitstring;
use PHPixieCron\Expression\StringParser;

class DayOfWeek extends AbstractField
{
    protected int $lowBit = 0;
    protected int $highBit = 7;

    /**
     * @var array<int, string>
     */
    protected array $names = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    public function __construct(protected string $expression)
    {
        $this->bitstring = new Bitstring($this->lowBit, $this->highBit);
        if ($expression === '?') {
            for ($i = $this->lowBit; $i <= $this->highBit; $i++) {
                $this->bitstring->setElement($i);
            }
        } elseif (strpos($expression, 'L')) {
            // We'll have to handle isSatisfiedBy without a Bistring since this
            // value will be transient
        } elseif (strpos($expression, '#')) {
            // We'll have to handle isSatisfiedBy without a Bistring since this
            // value will be transient
        } else {
            StringParser::parse($expression, $this->bitstring, $this->names);
        }
    }

    public function isSatisfiedBy(\DateTimeInterface $time): bool
    {
        $currentYear = (int) $time->format('Y');
        $currentMonth = (int) $time->format('m');
        $lastDayOfMonth = (int) $time->format('t');

        if ($lPosition = strpos($this->expression, 'L')) {
            $weekday = $this->convertLiteral(substr($this->expression, 0, $lPosition));
            $weekday %= 7;

            $tdate = clone $time;
            /** @phpstan-ignore-next-line */
            $tdate = $tdate->setDate($currentYear, $currentMonth, $lastDayOfMonth);
            while ($tdate->format('w') != $weekday) {
                $tdateClone = new \DateTime();
                $tdate = $tdateClone->setTimezone($tdate->getTimezone())
                    ->setDate($currentYear, $currentMonth, --$lastDayOfMonth);
            }

            return (int) $time->format('j') === $lastDayOfMonth;
        }

        if (strpos($this->expression, '#')) {
            [$weekday, $nth] = explode('#', $this->expression);

            if (!is_numeric($nth)) {
                throw new \InvalidArgumentException("Hashed weekdays must be numeric, {$nth} given");
            } else {
                $nth = (int) $nth;
            }

            // 0 and 7 are both Sunday, however 7 matches date('N') format ISO-8601
            if ('0' === $weekday) {
                $weekday = 7;
            }

            $weekday = (int) $this->convertLiteral((string) $weekday);

            // Validate the hash fields
            if ($weekday < 0 || $weekday > 7) {
                throw new \InvalidArgumentException("Weekday must be a value between 0 and 7. {$weekday} given");
            }

            if ($nth > 5 || $nth < 1) {
                throw new \InvalidArgumentException("There are never more than 5 or less than 1 of a given weekday in a month, {$nth} given");
            }

            // The current weekday must match the targeted weekday to proceed
            if ((int) $time->format('N') !== $weekday) {
                return false;
            }

            $tdate = clone $time;
            /** @phpstan-ignore-next-line */
            $tdate = $tdate->setDate($currentYear, $currentMonth, 1);
            $dayCount = 0;
            $currentDay = 1;
            while ($currentDay < $lastDayOfMonth + 1) {
                if ((int) $tdate->format('N') === $weekday) {
                    if (++$dayCount >= $nth) {
                        break;
                    }
                }
                $tdate = $tdate->setDate($currentYear, $currentMonth, ++$currentDay);
            }

            return (int) $time->format('j') === $currentDay;
        }

        return $this->bitstring->testBit((int) $time->format('N'));
    }
}
