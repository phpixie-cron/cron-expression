<?php

declare(strict_types=1);

namespace PHPixieCron\Expression;

use PHPixieCron\Expression\Field\AbstractField;
use PHPixieCron\Expression\Field\DayOfMonth;
use PHPixieCron\Expression\Field\DayOfWeek;
use PHPixieCron\Expression\Field\Hour;
use PHPixieCron\Expression\Field\Minute;
use PHPixieCron\Expression\Field\Month;

/**
 * Container for a Cron entry
 *
 * @package Cron
 */
class Entry
{
    public const E_MINUTE = 'bad minute';
    public const E_HOUR = 'bad hour';
    public const E_DOM = 'bad day-of-month';
    public const E_MONTH = 'bad month';
    public const E_DOW = 'bad day-of-week';
    public const E_TIMESPEC = 'bad time specifier';

    public const MINUTE_STAR = 1;
    public const HOUR_STAR = 2;
    public const DOM_STAR = 4;
    public const MONTH_STAR = 8;
    public const DOW_STAR = 16;

    public const EOF = null;

    protected AbstractField $minute;
    protected AbstractField $hour;
    protected AbstractField $dom;
    protected AbstractField $month;
    protected AbstractField $dow;
    protected StringReader $stringReader;

    /**
     * @var array<string>
     */
    public static array $order = [
        'minute',
        'hour',
        'dom',
        'month',
        'dow'
    ];

    protected int $slot = 0;
    protected int $flags = 0;

    public function __construct(protected string $expression)
    {
        if ($this->expression[0] === '-') {
            // cronie allows starting with - to disable logging. Move to the next character. Chop and move on.
            $this->expression = substr($this->expression, 1);
        }

        if ($this->expression[0] === '@') {
            switch (strtolower($this->expression)) {
                case "@yearly":
                    $this->minute = new Minute('0');
                    $this->hour = new Hour('0');
                    $this->dom = new DayOfMonth('1');
                    $this->month = new Month('1');
                    $this->dow = new DayOfWeek('*');
                    $this->flags |= static::DOW_STAR;
                    break;
                case "@monthly":
                    $this->minute = new Minute('0');
                    $this->hour = new Hour('0');
                    $this->dom = new DayOfMonth('1');
                    $this->month = new Month('*');
                    $this->dow = new DayOfWeek('*');
                    $this->flags |= static::DOW_STAR;
                    break;
                case "@weekly":
                    $this->minute = new Minute('0');
                    $this->hour = new Hour('0');
                    $this->dom = new DayOfMonth('*');
                    $this->month = new Month('*');
                    $this->dow = new DayOfWeek('0');
                    $this->flags |= static::DOM_STAR;
                    break;
                case "@daily":
                case "@midnight":
                    $this->minute = new Minute('0');
                    $this->hour = new Hour('0');
                    $this->dom = new DayOfMonth('*');
                    $this->month = new Month('*');
                    $this->dow = new DayOfWeek('*');
                    break;
                case "@hourly":
                    $this->minute = new Minute('0');
                    $this->hour = new Hour('*');
                    $this->dom = new DayOfMonth('*');
                    $this->month = new Month('*');
                    $this->dow = new DayOfWeek('*');
                    $this->flags |= static::HOUR_STAR;
                    break;
                default:
                    throw new \InvalidArgumentException(static::E_TIMESPEC);
            }
        } else {
            $parts = explode(' ', $this->expression);
            $this->minute = new Minute($parts[0]);
            $this->hour = new Hour($parts[1]);
            $this->dom = new DayOfMonth($parts[2]);
            $this->month = new Month($parts[3]);
            $this->dow = new DayOfWeek($parts[4]);

            foreach (static::$order as $key => $name) {
                if ($parts[$key] === '*') {
                    $this->flags |= constant(get_class() . '::' . strtoupper($name) . '_STAR');
                }
            }
        }

        if ($this->dow->testBit(0) || $this->dow->testBit(7)) {
            $this->dow->setBit(0);
            $this->dow->setBit(7);
        }
    }

    public function getField(string $name): AbstractField
    {
        return match ($name) {
            'minute' => $this->getMinute(),
            'hour' => $this->getHour(),
            'dom' => $this->getDayOfMonth(),
            'month' => $this->getMonth(),
            'dow' => $this->getDayOfWeek(),
            default => throw new \RuntimeException('Unknown entry field ' . $name)
        };
    }

    public function getMinute(): AbstractField
    {
        return $this->minute;
    }

    public function getHour(): AbstractField
    {
        return $this->hour;
    }

    public function getDayOfMonth(): AbstractField
    {
        return $this->dom;
    }

    public function getMonth(): AbstractField
    {
        return $this->month;
    }

    public function getDayOfWeek(): AbstractField
    {
        return $this->dow;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }
}
