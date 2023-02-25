<?php

namespace Cron\Tests;

use PHPixieCron\Expression\CronExpression;
use PHPUnit\Framework\TestCase;

class CronExpressionTest extends TestCase
{
    /**
     * @dataProvider scheduleProvider
     */
    public function testExpressions(string $schedule, \DateTimeInterface $relativeTime, \DateTimeInterface $nextRun, bool $isDue)
    {
        $e = new CronExpression($schedule);
        $this->assertSame($isDue, $e->isDue($relativeTime));
        $this->assertEquals($nextRun, $e->getNextRunDate($relativeTime));
    }

    public function scheduleProvider(): array
    {
        return [
            ['*/2 */2 * * *', new \DateTimeImmutable('2015-08-10 21:47:27'), new \DateTimeImmutable('2015-08-10 22:00:00'), false],
            ['* * * * *', new \DateTimeImmutable('2015-08-10 21:50:37'), new \DateTimeImmutable('2015-08-10 21:50:00'), true],
            ['* 20,21,22 * * *', new \DateTimeImmutable('2015-08-10 21:50:00'), new \DateTimeImmutable('2015-08-10 21:50:00'), true],
            // Handles CSV values
            ['* 20,22 * * *', new \DateTimeImmutable('2015-08-10 21:50:00'), new \DateTimeImmutable('2015-08-10 22:00:00'), false],
            // CSV values can be complex
            ['7-9 * */9 * *', new \DateTimeImmutable('2015-08-10 22:02:33'), new \DateTimeImmutable('2015-08-10 22:07:00'), false],
            // 15th minute, of the second hour, every 15 days, in January, every Friday
            ['1 * * * 7', new \DateTimeImmutable('2015-08-10 21:47:27'), new \DateTimeImmutable('2015-08-16 00:01:00'), false],
            // Test with exact times
            ['47 21 * * *', new \DateTimeImmutable('2015-08-10 21:47:30'), new \DateTimeImmutable('2015-08-10 21:47:00'), true],
            // Test Day of the week (issue #1)
            // According cron implementation, 0|7 = sunday, 1 => monday, etc
            ['* * * * 0', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2011-06-19 00:00:00'), false],
            ['* * * * 7', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2011-06-19 00:00:00'), false],
            ['* * * * 1', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2011-06-20 00:00:00'), false],
            // Should return the sunday date as 7 equals 0
            ['0 0 * * MON,SUN', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2011-06-19 00:00:00'), false],
            ['0 0 * * 1,7', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2011-06-19 00:00:00'), false],
            ['0 0 * * 0-4', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2011-06-16 00:00:00'), false],
            ['0 0 * * 4-7', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2011-06-16 00:00:00'), false],
            ['0 0 * * 3-7', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2011-06-16 00:00:00'), false],
            ['0 0 * * 3-7', new \DateTimeImmutable('2011-06-18 23:09:00'), new \DateTimeImmutable('2011-06-19 00:00:00'), false],
            // Test lists of values and ranges (Abhoryo)
            ['0 0 * * 2-7', new \DateTimeImmutable('2011-06-20 23:09:00'), new \DateTimeImmutable('2011-06-21 00:00:00'), false],
            ['0 0 * * 2-7', new \DateTimeImmutable('2011-06-18 23:09:00'), new \DateTimeImmutable('2011-06-19 00:00:00'), false],
            ['0 0 * * 4-7', new \DateTimeImmutable('2011-07-19 00:00:00'), new \DateTimeImmutable('2011-07-21 00:00:00'), false],
            // Test increments of ranges
            ['0-12/4 * * * *', new \DateTimeImmutable('2011-06-20 12:04:00'), new \DateTimeImmutable('2011-06-20 12:04:00'), true],
            ['4-59/2 * * * *', new \DateTimeImmutable('2011-06-20 12:04:00'), new \DateTimeImmutable('2011-06-20 12:04:00'), true],
            ['4-59/2 * * * *', new \DateTimeImmutable('2011-06-20 12:06:00'), new \DateTimeImmutable('2011-06-20 12:06:00'), true],
            ['4-59/3 * * * *', new \DateTimeImmutable('2011-06-20 12:06:00'), new \DateTimeImmutable('2011-06-20 12:07:00'), false],
            // Test Day of the Week and the Day of the Month (issue #1)
            ['0 0 1 1 0', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2012-01-01 00:00:00'), false],
            ['0 0 1 JAN 0', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2012-01-01 00:00:00'), false],
            ['0 0 1 * 0', new \DateTimeImmutable('2011-06-15 23:09:00'), new \DateTimeImmutable('2011-06-19 00:00:00'), false],
            // Test the W day of the week modifier for day of the month field
            ['0 0 2W * *', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-01 00:00:00'), true],
            ['0 0 1W * *', new \DateTimeImmutable('2011-05-01 00:00:00'), new \DateTimeImmutable('2011-05-02 00:00:00'), false],
            ['0 0 1W * *', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-01 00:00:00'), true],
            ['0 0 3W * *', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-04 00:00:00'), false],
            ['0 0 16W * *', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-15 00:00:00'), false],
            ['0 0 28W * *', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-28 00:00:00'), false],
            ['0 0 30W * *', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-29 00:00:00'), false],
            ['0 0 31W * *', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-29 00:00:00'), false],
            // Test the last weekday of a month
            ['* * * * 5L', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-29 00:00:00'), false],
            ['* * * * 6L', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-30 00:00:00'), false],
            ['* * * * 7L', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-31 00:00:00'), false],
            ['* * * * 1L', new \DateTimeImmutable('2011-07-24 00:00:00'), new \DateTimeImmutable('2011-07-25 00:00:00'), false],
            ['* * * 1 5L', new \DateTimeImmutable('2011-12-25 00:00:00'), new \DateTimeImmutable('2012-01-27 00:00:00'), false],
            // Test the last day of the month
            ['* * L * *', new \DateTimeImmutable('2011-12-25 00:00:00'), new \DateTimeImmutable('2011-12-31 00:00:00'), false],
            // Test the hash symbol for the nth weekday of a given month
            ['* * * * 5#2', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-08 00:00:00'), false],
            ['* * * * 5#1', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-01 00:00:00'), true],
            ['* * * * 3#4', new \DateTimeImmutable('2011-07-01 00:00:00'), new \DateTimeImmutable('2011-07-27 00:00:00'), false],

            // Issue #7, documented example failed
            ['3-59/15 6-12 */15 1 2-5', new \DateTimeImmutable('2017-01-08 00:00:00'), new \DateTimeImmutable('2017-01-10 06:03:00'), false],

            // https://github.com/laravel/framework/commit/07d160ac3cc9764d5b429734ffce4fa311385403
            ['* * * * MON-FRI', new \DateTimeImmutable('2017-01-08 00:00:00'), new \DateTimeImmutable('2017-01-09 00:00:00'), false],
            ['* * * * TUE', new \DateTimeImmutable('2017-01-08 00:00:00'), new \DateTimeImmutable('2017-01-10 00:00:00'), false],

            // Issue #60, make sure that casing is less relevant for shortcuts, months, and days
            ['0 1 15 JUL mon,Wed,FRi', new \DateTimeImmutable('2019-11-14 00:00:00'), new \DateTimeImmutable('2020-07-01 01:00:00'), false],
            ['0 1 15 jul mon,Wed,FRi', new \DateTimeImmutable('2019-11-14 00:00:00'), new \DateTimeImmutable('2020-07-01 01:00:00'), false],
            ['@Weekly', new \DateTimeImmutable('2019-11-14 00:00:00'), new \DateTimeImmutable('2019-11-17 00:00:00'), false],
            ['@WEEKLY', new \DateTimeImmutable('2019-11-14 00:00:00'), new \DateTimeImmutable('2019-11-17 00:00:00'), false],
            ['@WeeklY', new \DateTimeImmutable('2019-11-14 00:00:00'), new \DateTimeImmutable('2019-11-17 00:00:00'), false],

            // Issue #76, DOW and DOM do not support ?
            ['0 12 * * ?', new \DateTimeImmutable('2020-08-20 00:00:00'), new \DateTimeImmutable('2020-08-20 12:00:00'), false],
            ['0 12 ? * *', new \DateTimeImmutable('2020-08-20 00:00:00'), new \DateTimeImmutable('2020-08-20 12:00:00'), false],

            // PR #122 - Better handling steps, lists, and ranges
            ['0 */3,1,1-12 * * *', new \DateTimeImmutable('2020-08-20 00:05:00'), new \DateTimeImmutable('2020-08-20 01:00:00'), false],

            ['0-59/59 10 * * *', new \DateTimeImmutable('2021-08-25 10:00:00'), new \DateTimeImmutable('2021-08-25 10:00:00'), true],
            ['0-59/59 10 * * *', new \DateTimeImmutable('2021-08-25 09:00:00'), new \DateTimeImmutable('2021-08-25 10:00:00'), false],
            ['0-59/59 10 * * *', new \DateTimeImmutable('2021-08-25 10:01:00'), new \DateTimeImmutable('2021-08-25 10:59:00'), false],
            ['41-59/24 5 * * *', new \DateTimeImmutable('2021-08-25 10:00:00'), new \DateTimeImmutable('2021-08-26 05:41:00'), false],
        ];
    }
}
