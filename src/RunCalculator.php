<?php

declare(strict_types=1);

namespace PHPixieCron\Expression;

/**
 * Determines next/previous run dates based on crontab entries
 *
 * @package Cron
 */
class RunCalculator
{
    public function __construct(protected Entry $entry)
    {
    }

    public function getNextRunDate(?\DateTimeInterface $start = null): \DateTimeInterface
    {
        if (is_null($start)) {
            $start = new \DateTimeImmutable();
        }
        /** @phpstan-ignore-next-line */
        $start = $start->setTime((int) $start->format('H'), (int) $start->format('i'), 0, 0);
        $end = $start->add(new \DateInterval('P8Y'));

        for ($time = clone $start; $time < $end;) {
            // If the month doesn't match, move to the 1st of the next month
            if (!$this->entry->getMonth()->testBit($time->format('m') - 1)) {
                $time = $time->add(new \DateInterval('P1M'));
                $time = $time->setDate((int) $time->format('Y'), (int) $time->format('m'), 1)->setTime(0, 0, 0, 0);
                continue;
            }

            // Neither the time nor time+1 day match, add a day
            if (!$this->matchDay($time) && !$this->matchDay($time->add(new \DateInterval('P1D')))) {
                $time = $time->add(new \DateInterval('P1D'));
                continue;
            }

            if (
                $this->entry->getMonth()->isSatisfiedBy($time)
                && $this->matchDay($time)
                && $this->entry->getHour()->isSatisfiedBy($time)
                && $this->entry->getMinute()->isSatisfiedBy($time)
            ) {
                return $time;
            }

            $time = $time->add(new \DateInterval('PT1M'));
        }

        throw new \RuntimeException('Unable to determine a future run date');
    }

    /**
     * Figure out whether to look at DOW or DOM for matching
     */
    protected function matchDay(\DateTimeInterface $time): bool
    {
        if ($this->entry->getFlags() & Entry::DOW_STAR) {
            return $this->entry->getDayOfMonth()->isSatisfiedBy($time);
        }

        if ($this->entry->getFlags() & Entry::DOM_STAR) {
            return $this->entry->getDayOfWeek()->isSatisfiedBy($time);
        }

        $dom = $this->entry->getDayOfMonth()->isSatisfiedBy($time);
        $dow = $this->entry->getDayOfWeek()->isSatisfiedBy($time);
        return $dom || $dow;
    }
}
