<?php

declare(strict_types=1);

namespace Icalendar\Recurrence;

/**
 * Immutable value object representing a parsed RRULE (recurrence rule)
 *
 * Stores all components of an RRULE according to RFC 5545 §3.3.10.
 * This class is immutable - once created, it cannot be modified.
 *
 * @immutable
 */
readonly class RRule
{
    /**
     * Valid frequency values
     */
    public const FREQ_SECONDLY = 'SECONDLY';
    public const FREQ_MINUTELY = 'MINUTELY';
    public const FREQ_HOURLY = 'HOURLY';
    public const FREQ_DAILY = 'DAILY';
    public const FREQ_WEEKLY = 'WEEKLY';
    public const FREQ_MONTHLY = 'MONTHLY';
    public const FREQ_YEARLY = 'YEARLY';

    /**
     * Valid days of the week
     */
    public const DAY_SUNDAY = 'SU';
    public const DAY_MONDAY = 'MO';
    public const DAY_TUESDAY = 'TU';
    public const DAY_WEDNESDAY = 'WE';
    public const DAY_THURSDAY = 'TH';
    public const DAY_FRIDAY = 'FR';
    public const DAY_SATURDAY = 'SA';

    /**
     * @param string $freq Frequency: SECONDLY, MINUTELY, HOURLY, DAILY, WEEKLY, MONTHLY, YEARLY
     * @param int $interval Interval between occurrences (default: 1)
     * @param int|null $count Number of occurrences (mutually exclusive with until)
     * @param \DateTimeImmutable|null $until End date/time (mutually exclusive with count)
     * @param array<int> $bySecond List of seconds (0-60)
     * @param array<int> $byMinute List of minutes (0-59)
     * @param array<int> $byHour List of hours (0-23)
     * @param array<array{day: string, ordinal: int|null}> $byDay List of days with optional ordinal (e.g., ['day' => 'MO', 'ordinal' => 2])
     * @param array<int> $byMonthDay List of month days (1-31 or -31 to -1)
     * @param array<int> $byYearDay List of year days (1-366 or -366 to -1)
     * @param array<int> $byWeekNo List of week numbers (1-53 or -53 to -1)
     * @param array<int> $byMonth List of months (1-12)
     * @param array<int> $bySetPos Position filters for BY* results (1-366 or -366 to -1)
     * @param string $wkst Week start day (default: MO)
     */
    public function __construct(
        private string $freq,
        private int $interval = 1,
        private ?int $count = null,
        private ?\DateTimeImmutable $until = null,
        private array $bySecond = [],
        private array $byMinute = [],
        private array $byHour = [],
        private array $byDay = [],
        private array $byMonthDay = [],
        private array $byYearDay = [],
        private array $byWeekNo = [],
        private array $byMonth = [],
        private array $bySetPos = [],
        private string $wkst = 'MO'
    ) {
    }

    /**
     * Get frequency (FREQ)
     */
    public function getFreq(): string
    {
        return $this->freq;
    }

    /**
     * Get interval
     */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * Get count limit
     */
    public function getCount(): ?int
    {
        return $this->count;
    }

    /**
     * Get until date
     */
    public function getUntil(): ?\DateTimeImmutable
    {
        return $this->until;
    }

    /**
     * Get BYSECOND values
     * @return array<int>
     */
    public function getBySecond(): array
    {
        return $this->bySecond;
    }

    /**
     * Get BYMINUTE values
     * @return array<int>
     */
    public function getByMinute(): array
    {
        return $this->byMinute;
    }

    /**
     * Get BYHOUR values
     * @return array<int>
     */
    public function getByHour(): array
    {
        return $this->byHour;
    }

    /**
     * Get BYDAY values
     * @return array<array{day: string, ordinal: int|null}>
     */
    public function getByDay(): array
    {
        return $this->byDay;
    }

    /**
     * Get BYMONTHDAY values
     * @return array<int>
     */
    public function getByMonthDay(): array
    {
        return $this->byMonthDay;
    }

    /**
     * Get BYYEARDAY values
     * @return array<int>
     */
    public function getByYearDay(): array
    {
        return $this->byYearDay;
    }

    /**
     * Get BYWEEKNO values
     * @return array<int>
     */
    public function getByWeekNo(): array
    {
        return $this->byWeekNo;
    }

    /**
     * Get BYMONTH values
     * @return array<int>
     */
    public function getByMonth(): array
    {
        return $this->byMonth;
    }

    /**
     * Get BYSETPOS values
     * @return array<int>
     */
    public function getBySetPos(): array
    {
        return $this->bySetPos;
    }

    /**
     * Get week start (WKST)
     */
    public function getWkst(): string
    {
        return $this->wkst;
    }

    /**
     * Check if COUNT is set (occurrence limit)
     */
    public function hasCount(): bool
    {
        return $this->count !== null;
    }

    /**
     * Check if UNTIL is set (end date limit)
     */
    public function hasUntil(): bool
    {
        return $this->until !== null;
    }

    /**
     * Parse an RRULE string into an immutable RRule object
     *
     * @param string $rrule The RRULE string (e.g., "FREQ=DAILY;COUNT=10")
     * @return RRule The parsed, immutable recurrence rule
     * @throws \InvalidArgumentException If the RRULE format is invalid
     */
    public static function parse(string $rrule): self
    {
        $parser = new RRuleParser();
        return $parser->parse($rrule);
    }

    /**
     * Convert back to RRULE string
     */
    public function toString(): string
    {
        $parts = ['FREQ=' . $this->freq];

        if ($this->interval !== 1) {
            $parts[] = 'INTERVAL=' . $this->interval;
        }

        if ($this->count !== null) {
            $parts[] = 'COUNT=' . $this->count;
        }

        if ($this->until !== null) {
            $parts[] = 'UNTIL=' . $this->until->format('Ymd\THis');
            if ($this->until->getTimezone()->getName() === 'UTC') {
                $parts[count($parts) - 1] .= 'Z';
            }
        }

        if (!empty($this->bySecond)) {
            $parts[] = 'BYSECOND=' . implode(',', $this->bySecond);
        }

        if (!empty($this->byMinute)) {
            $parts[] = 'BYMINUTE=' . implode(',', $this->byMinute);
        }

        if (!empty($this->byHour)) {
            $parts[] = 'BYHOUR=' . implode(',', $this->byHour);
        }

        if (!empty($this->byDay)) {
            $dayParts = [];
            foreach ($this->byDay as $dayInfo) {
                $dayStr = '';
                if ($dayInfo['ordinal'] !== null) {
                    $dayStr .= $dayInfo['ordinal'];
                }
                $dayStr .= $dayInfo['day'];
                $dayParts[] = $dayStr;
            }
            $parts[] = 'BYDAY=' . implode(',', $dayParts);
        }

        if (!empty($this->byMonthDay)) {
            $parts[] = 'BYMONTHDAY=' . implode(',', $this->byMonthDay);
        }

        if (!empty($this->byYearDay)) {
            $parts[] = 'BYYEARDAY=' . implode(',', $this->byYearDay);
        }

        if (!empty($this->byWeekNo)) {
            $parts[] = 'BYWEEKNO=' . implode(',', $this->byWeekNo);
        }

        if (!empty($this->byMonth)) {
            $parts[] = 'BYMONTH=' . implode(',', $this->byMonth);
        }

        if (!empty($this->bySetPos)) {
            $parts[] = 'BYSETPOS=' . implode(',', $this->bySetPos);
        }

        if ($this->wkst !== 'MO') {
            $parts[] = 'WKST=' . $this->wkst;
        }

        return implode(';', $parts);
    }

    /**
     * Create a new RRule with modified frequency
     */
    public function withFreq(string $freq): self
    {
        return new self(
            $freq,
            $this->interval,
            $this->count,
            $this->until,
            $this->bySecond,
            $this->byMinute,
            $this->byHour,
            $this->byDay,
            $this->byMonthDay,
            $this->byYearDay,
            $this->byWeekNo,
            $this->byMonth,
            $this->bySetPos,
            $this->wkst
        );
    }

    /**
     * Create a new RRule with modified interval
     */
    public function withInterval(int $interval): self
    {
        return new self(
            $this->freq,
            $interval,
            $this->count,
            $this->until,
            $this->bySecond,
            $this->byMinute,
            $this->byHour,
            $this->byDay,
            $this->byMonthDay,
            $this->byYearDay,
            $this->byWeekNo,
            $this->byMonth,
            $this->bySetPos,
            $this->wkst
        );
    }

    /**
     * Create a new RRule with modified count
     */
    public function withCount(?int $count): self
    {
        return new self(
            $this->freq,
            $this->interval,
            $count,
            $this->until,
            $this->bySecond,
            $this->byMinute,
            $this->byHour,
            $this->byDay,
            $this->byMonthDay,
            $this->byYearDay,
            $this->byWeekNo,
            $this->byMonth,
            $this->bySetPos,
            $this->wkst
        );
    }

    /**
     * Create a new RRule with modified until date
     */
    public function withUntil(?\DateTimeImmutable $until): self
    {
        return new self(
            $this->freq,
            $this->interval,
            $this->count,
            $until,
            $this->bySecond,
            $this->byMinute,
            $this->byHour,
            $this->byDay,
            $this->byMonthDay,
            $this->byYearDay,
            $this->byWeekNo,
            $this->byMonth,
            $this->bySetPos,
            $this->wkst
        );
    }
}