<?php

declare(strict_types=1);

namespace PHPixieCron\Expression;

use DateTimeImmutable;

/**
 * Wrapper for testing cron expressions and getting additional information
 *
 * @package Cron
 */
class CronExpression
{
    protected Entry $entry;

    public function __construct(protected string $expression)
    {
        $this->entry = new Entry($this->expression);
    }

    public static function isValidExpression(string $expression): bool
    {
        try {
            new Entry($expression);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function isDue(\DateTimeInterface $time = null): bool
    {
        if (is_null($time)) {
            $time = new \DateTimeImmutable();
        }

        return
            $this->entry->getMinute()->isSatisfiedBy($time)
            && $this->entry->getHour()->isSatisfiedBy($time)
            && $this->entry->getDayOfMonth()->isSatisfiedBy($time)
            && $this->entry->getMonth()->isSatisfiedBy($time)
            && $this->entry->getDayOfWeek()->isSatisfiedBy($time)
        ;
    }

    public function getEntry(): Entry
    {
        return $this->entry;
    }

    public function getNextRunDate(?\DateTimeInterface $date = null): \DateTimeInterface
    {
        if (is_null($date)) {
            $date = new DateTimeImmutable();
        }

        $calculator = new RunCalculator($this->entry);
        return $calculator->getNextRunDate($date);
    }
}
