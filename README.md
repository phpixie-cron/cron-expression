PHPixieCron Cron Expression Parser
==================================

[![Latest Stable Version](https://poser.pugx.org/phpixie-cron/cron-expression/v/stable.png)](https://packagist.org/packages/phpixie-cron/cron-expression) [![Total Downloads](https://poser.pugx.org/phpixie-cron/cron-expression/downloads.png)](https://packagist.org/packages/phpixie-cron/cron-expression)

The PHPixieCron Cron Expression Parser can parse a CRON expression, determine if it is
due to run, and calculate the next run date of the expression.

The parser can handle increments of ranges (e.g. */12, 2-59/3), intervals (e.g. 0-9),
lists (e.g. 1,2,3), **W** to find the nearest weekday for a given day of the month, **L** to
find the last day of the month, **L** to find the last given weekday of a month, and hash
(#) to find the nth weekday of a given month.

Why
---

This is a remake of the [dragonmantank/cron-expression](https://github.com/dragonmantank/cron-expression) package, but
not necessarily a direct replacement. During the 2.0 break from the original package, I took a stance that `cron-expression`
should follow the logic of `cronie`, one of the original cron packages, as close as possible. The concept of a job scheduler
like cron has it's roots in older POSIX implementations, which is as close to a standard as we can probably get.

The reason for this stance is there are many non-standard ways to parse and write a cron. The goal was to _not_ make a
more flexible cron but to make sure we could parse valid cron strings. The best way to do that was to adhere to what
little standards there are.

This package re-implements the [cronie](https://github.com/cronie-crond/cronie) parsing logic to the point the parsing
code is as close to the original C logic as I felt could be done in PHP. Many of the original parsing tests have been
ported over and continue to work. You should be able to switch to this package from the older `cron-expression`, assuming
you do not need to calculate multiple run dates, or past run dates. 

`cron-expression` is also, as of 2023, 12 years old. Much of the logic for determining run dates is old and complicated,
and there are plenty of coding choices that I would not make today. I feel that the massive uplift is basically a new
package, so I'm taking the time to do so. Rewriting and simplifying this package will also open the way to make more
tools on top of it, one of which is a proper cron system in PHP. 

Installing
==========

Add the dependency to your project:

```bash
composer require phpixie-cron/cron-expression
```

Usage
=====
```php
<?php

require_once '/vendor/autoload.php';

use PHPixeCron\Expression\CronExpression;

// Works with predefined scheduling definitions
$cron = new CronExpression('@daily');
$cron->isDue();
echo $cron->getNextRunDate()->format('Y-m-d H:i:s');

// Works with complex expressions
$cron = new CronExpression('3-59/15 6-12 */15 1 2-5');
echo $cron->getNextRunDate()->format('Y-m-d H:i:s');

// Calculate a run date two iterations into the future
$cron = new CronExpression('@daily');
echo $cron->getNextRunDate(null, 2)->format('Y-m-d H:i:s');

// Calculate a run date relative to a specific time
$cron = new CronExpression('@monthly');
echo $cron->getNextRunDate('2010-01-12 00:00:00')->format('Y-m-d H:i:s');
```

CRON Expressions
================

A CRON expression is a string representing the schedule for a particular command to execute.  The parts of a CRON schedule are as follows:

    *    *    *    *    *
    -    -    -    -    -
    |    |    |    |    |
    |    |    |    |    |
    |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
    |    |    |    +---------- month (1 - 12)
    |    |    +--------------- day of month (1 - 31)
    |    +-------------------- hour (0 - 23)
    +------------------------- min (0 - 59)

This library also supports a few macros:

* `@yearly`, `@annually` - Run once a year, midnight, Jan. 1 - `0 0 1 1 *`
* `@monthly` - Run once a month, midnight, first of month - `0 0 1 * *`
* `@weekly` - Run once a week, midnight on Sun - `0 0 * * 0`
* `@daily`, `@midnight` - Run once a day, midnight - `0 0 * * *`
* `@hourly` - Run once an hour, first minute - `0 * * * *`

Requirements
============

- PHP 8.0+
- PHPUnit is required to run the unit tests
- Composer is required to run the unit tests

