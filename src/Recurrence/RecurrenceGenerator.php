<?php

declare(strict_types=1);

namespace Icalendar\Recurrence;

/**
 * Generates recurrence instances from RRULE patterns
 *
 * Implements instance generation according to RFC 5545 §3.3.10.
 * Uses generator pattern for memory efficiency when dealing with
 * large numbers of occurrences.
 *
 * @see RRule
 */
class RecurrenceGenerator
{
    /** @var array<string, int> Day name to numeric day of week mapping (0=Sunday, 6=Saturday) */
    private const DAY_MAP = [
        'SU' => 0,
        'MO' => 1,
        'TU' => 2,
        'WE' => 3,
        'TH' => 4,
        'FR' => 5,
        'SA' => 6,
    ];

    /**
     * Generate recurrence instances from an RRULE
     *
     * @param RRule $rule The recurrence rule
     * @param \DateTimeInterface $dtstart The start date/time of the first occurrence
     * @param \DateTimeInterface|null $rangeEnd Optional end date to stop generation
     * @param array<\DateTimeInterface> $exdates Optional array of excluded dates
     * @param array<\DateTimeInterface> $rdates Optional array of additional dates
     * @return \Generator<\DateTimeImmutable> Yields one occurrence at a time
     */
    public function generate(
        RRule $rule,
        \DateTimeInterface $dtstart,
        ?\DateTimeInterface $rangeEnd = null,
        array $exdates = [],
        array $rdates = []
    ): \Generator {
        $occurrenceCount = 0;
        $current = \DateTimeImmutable::createFromInterface($dtstart);
        
        // Convert exdates and rdates to comparable format
        $exdateSet = $this->buildDateSet($exdates);
        
        // Convert and sort RDATEs
        $sortedRdates = [];
        foreach ($rdates as $rdate) {
            $sortedRdates[] = \DateTimeImmutable::createFromInterface($rdate);
        }
        usort($sortedRdates, function ($a, $b) {
            return $a->getTimestamp() <=> $b->getTimestamp();
        });
        
        // Determine the effective end date
        $until = $rule->getUntil();
        $effectiveEnd = $this->determineEndDate($until, $rangeEnd);
        
        // For COUNT-based rules, we need to count actual occurrences
        $maxCount = $rule->getCount();
        
        // Generate base occurrences based on FREQ
        $freq = $rule->getFreq();
        $interval = $rule->getInterval();
        
        // Generate base set and apply BY* filters
        $candidates = $this->generateCandidates($rule, $current, $effectiveEnd);
        
        $rdateIndex = 0;
        
        foreach ($candidates as $candidate) {
            // Skip if before dtstart
            if ($candidate < $current) {
                continue;
            }
            
            // Check COUNT limit
            if ($maxCount !== null && $occurrenceCount >= $maxCount) {
                break;
            }
            
            // Check UNTIL limit
            if ($until !== null && $candidate > $until) {
                break;
            }
            
            // Check range end
            if ($effectiveEnd !== null && $candidate > $effectiveEnd) {
                break;
            }
            
            // Skip excluded dates
            if ($this->isDateInSet($candidate, $exdateSet)) {
                continue;
            }
            
            // Yield any RDATEs that come before this candidate
            while ($rdateIndex < count($sortedRdates) && $sortedRdates[$rdateIndex] < $candidate) {
                $rdate = $sortedRdates[$rdateIndex];
                
                // Check limits for RDATE
                if ($maxCount !== null && $occurrenceCount >= $maxCount) {
                    break 2;
                }
                if ($until !== null && $rdate > $until) {
                    break;
                }
                if ($effectiveEnd !== null && $rdate > $effectiveEnd) {
                    break;
                }
                if (!$this->isDateInSet($rdate, $exdateSet) && $rdate >= $current) {
                    yield $rdate;
                    $occurrenceCount++;
                }
                $rdateIndex++;
            }
            
            // This is a valid occurrence
            yield $candidate;
            $occurrenceCount++;
        }
        
        // Yield remaining RDATEs that come after all RRULE occurrences
        while ($rdateIndex < count($sortedRdates)) {
            $rdate = $sortedRdates[$rdateIndex];
            
            // Check COUNT limit
            if ($maxCount !== null && $occurrenceCount >= $maxCount) {
                break;
            }
            
            // Check UNTIL limit
            if ($until !== null && $rdate > $until) {
                $rdateIndex++;
                continue;
            }
            
            // Check range end
            if ($effectiveEnd !== null && $rdate > $effectiveEnd) {
                $rdateIndex++;
                continue;
            }
            
            // Skip excluded dates
            if ($this->isDateInSet($rdate, $exdateSet)) {
                $rdateIndex++;
                continue;
            }
            
            // Skip if before dtstart
            if ($rdate < $current) {
                $rdateIndex++;
                continue;
            }
            
            yield $rdate;
            $occurrenceCount++;
            $rdateIndex++;
        }
    }

    /**
     * Generate candidate dates based on FREQ and apply BY* filters
     *
     * @param RRule $rule
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable|null $end
     * @return \Generator<\DateTimeImmutable>
     */
    private function generateCandidates(RRule $rule, \DateTimeImmutable $start, ?\DateTimeImmutable $end): \Generator
    {
        $freq = $rule->getFreq();
        $interval = $rule->getInterval();
        
        switch ($freq) {
            case 'SECONDLY':
                yield from $this->generateSecondly($rule, $start, $end);
                break;
            case 'MINUTELY':
                yield from $this->generateMinutely($rule, $start, $end);
                break;
            case 'HOURLY':
                yield from $this->generateHourly($rule, $start, $end);
                break;
            case 'DAILY':
                yield from $this->generateDaily($rule, $start, $end);
                break;
            case 'WEEKLY':
                yield from $this->generateWeekly($rule, $start, $end);
                break;
            case 'MONTHLY':
                yield from $this->generateMonthly($rule, $start, $end);
                break;
            case 'YEARLY':
                yield from $this->generateYearly($rule, $start, $end);
                break;
            default:
                throw new \InvalidArgumentException('Unknown FREQ: ' . $freq);
        }
    }

    /**
     * Generate SECONDLY candidates
     */
    private function generateSecondly(RRule $rule, \DateTimeImmutable $start, ?\DateTimeImmutable $end): \Generator
    {
        $current = $start;
        $interval = $rule->getInterval();
        
        while ($end === null || $current <= $end) {
            // Apply BY* filters
            $candidates = $this->applyTimeFilters($rule, $current);
            
            foreach ($candidates as $candidate) {
                yield $candidate;
            }
            
            $current = $current->modify("+{$interval} seconds");
            
            // Safety limit
            if ($end === null && $this->getSecondsDiff($start, $current) > 31536000) { // 1 year
                break;
            }
        }
    }

    /**
     * Generate MINUTELY candidates
     */
    private function generateMinutely(RRule $rule, \DateTimeImmutable $start, ?\DateTimeImmutable $end): \Generator
    {
        $current = $start;
        $interval = $rule->getInterval();
        
        while ($end === null || $current <= $end) {
            // Reset to start of minute
            $minuteStart = $current->setTime(
                (int) $current->format('H'),
                (int) $current->format('i'),
                (int) $start->format('s')
            );
            
            $candidates = $this->applyTimeFilters($rule, $minuteStart);
            
            foreach ($candidates as $candidate) {
                yield $candidate;
            }
            
            $current = $current->modify("+{$interval} minutes");
            
            // Safety limit
            if ($end === null && $this->getSecondsDiff($start, $current) > 31536000) { // 1 year
                break;
            }
        }
    }

    /**
     * Generate HOURLY candidates
     */
    private function generateHourly(RRule $rule, \DateTimeImmutable $start, ?\DateTimeImmutable $end): \Generator
    {
        $current = $start;
        $interval = $rule->getInterval();
        
        while ($end === null || $current <= $end) {
            $hourStart = $current->setTime(
                (int) $current->format('H'),
                (int) $start->format('i'),
                (int) $start->format('s')
            );
            
            $candidates = $this->applyTimeFilters($rule, $hourStart);
            
            foreach ($candidates as $candidate) {
                yield $candidate;
            }
            
            $current = $current->modify("+{$interval} hours");
            
            // Safety limit
            if ($end === null && $this->getSecondsDiff($start, $current) > 31536000) { // 1 year
                break;
            }
        }
    }

    /**
     * Generate DAILY candidates
     */
    private function generateDaily(RRule $rule, \DateTimeImmutable $start, ?\DateTimeImmutable $end): \Generator
    {
        $current = $start;
        $interval = $rule->getInterval();
        
        while ($end === null || $current <= $end) {
            $dayStart = $current->setTime(
                (int) $start->format('H'),
                (int) $start->format('i'),
                (int) $start->format('s')
            );
            
            $candidates = $this->applyTimeFilters($rule, $dayStart);
            
            foreach ($candidates as $candidate) {
                yield $candidate;
            }
            
            $current = $current->modify("+{$interval} days");
            
            // Safety limit
            if ($end === null && $this->getSecondsDiff($start, $current) > 31536000 * 2) { // 2 years
                break;
            }
        }
    }

    /**
     * Generate WEEKLY candidates
     */
    private function generateWeekly(RRule $rule, \DateTimeImmutable $start, ?\DateTimeImmutable $end): \Generator
    {
        $interval = $rule->getInterval();
        $wkst = $rule->getWkst();
        $byDay = $rule->getByDay();
        
        // Get week start day
        $weekStartDay = self::DAY_MAP[$wkst];
        
        // Start from the beginning of the week containing dtstart
        $startWeekDay = (int) $start->format('w');
        $daysToWeekStart = ($startWeekDay - $weekStartDay + 7) % 7;
        $weekStart = $start->modify("-{$daysToWeekStart} days");
        
        $currentWeek = $weekStart;
        
        while ($end === null || $currentWeek <= $end) {
            // Generate all days in this week
            $weekCandidates = [];
            
            if (!empty($byDay)) {
                // Only yield specific days
                foreach ($byDay as $dayInfo) {
                    if ($dayInfo['ordinal'] !== null) {
                        continue; // Ordinals not valid in WEEKLY
                    }
                    
                    $dayName = $dayInfo['day'];
                    $targetDayOfWeek = self::DAY_MAP[$dayName];
                    $currentDayOfWeek = (int) $currentWeek->format('w');
                    
                    $daysToAdd = ($targetDayOfWeek - $currentDayOfWeek + 7) % 7;
                    $date = $currentWeek->modify("+{$daysToAdd} days");
                    $date = $date->setTime(
                        (int) $start->format('H'),
                        (int) $start->format('i'),
                        (int) $start->format('s')
                    );
                    
                    // Apply other BY* filters
                    if ($this->matchesByMonth($rule, $date) &&
                        $this->matchesByMonthDay($rule, $date) &&
                        $date >= $start) {
                        $weekCandidates[] = $date;
                    }
                }
            } else {
                // No BYDAY - yield the same day of week as dtstart
                $dtstartDayOfWeek = (int) $start->format('w');
                $currentDayOfWeek = (int) $currentWeek->format('w');
                
                $daysToAdd = ($dtstartDayOfWeek - $currentDayOfWeek + 7) % 7;
                $date = $currentWeek->modify("+{$daysToAdd} days");
                $date = $date->setTime(
                    (int) $start->format('H'),
                    (int) $start->format('i'),
                    (int) $start->format('s')
                );
                
                if ($date >= $start) {
                    $weekCandidates[] = $date;
                }
            }
            
            // Sort and apply BYSETPOS
            $weekCandidates = $this->sortAndUniqueDates($weekCandidates);
            $weekCandidates = $this->applyBySetPos($rule, $weekCandidates);
            
            foreach ($weekCandidates as $candidate) {
                yield $candidate;
            }
            
            // Move to next interval period
            $currentWeek = $currentWeek->modify("+{$interval} weeks");
            
            // Safety limit
            if ($end === null && $this->getSecondsDiff($start, $currentWeek) > 31536000 * 2) { // 2 years
                break;
            }
        }
    }

    /**
     * Generate MONTHLY candidates
     */
    private function generateMonthly(RRule $rule, \DateTimeImmutable $start, ?\DateTimeImmutable $end): \Generator
    {
        $current = $start;
        $interval = $rule->getInterval();
        $timezone = $start->getTimezone();
        
        while ($end === null || $current <= $end) {
            $year = (int) $current->format('Y');
            $month = (int) $current->format('n');
            
            $monthCandidates = [];
            
            // Generate candidates for this month
            $byMonthDay = $rule->getByMonthDay();
            $byDay = $rule->getByDay();
            
            if (!empty($byDay)) {
                // Generate by weekday with optional ordinal
                foreach ($byDay as $dayInfo) {
                    $dayName = $dayInfo['day'];
                    $ordinal = $dayInfo['ordinal'];

                    if ($ordinal !== null) {
                        // Specific nth occurrence (e.g., 2nd Tuesday)
                        $date = $this->getNthWeekdayOfMonth($year, $month, $dayName, $ordinal, $timezone);
                        if ($date !== null) {
                            $monthCandidates[] = $date;
                        }
                    } else {
                        // All occurrences of this day in the month
                        $dates = $this->getAllWeekdaysInMonth($year, $month, $dayName, $timezone);
                        foreach ($dates as $date) {
                            $monthCandidates[] = $date;
                        }
                    }
                }
            }
            // When both BYDAY and BYMONTHDAY are present, use OR logic (union)
            if (!empty($byMonthDay)) {
                foreach ($byMonthDay as $day) {
                    $date = $this->getDayOfMonth($year, $month, $day, $timezone);
                    if ($date !== null) {
                        $monthCandidates[] = $date;
                    }
                }
            }
            if (empty($byDay) && empty($byMonthDay)) {
                // Default: same day of month as DTSTART
                $day = (int) $start->format('j');
                $date = $this->getDayOfMonth($year, $month, $day, $timezone);
                if ($date !== null) {
                    $monthCandidates[] = $date;
                }
            }
            
            // Sort and deduplicate
            $monthCandidates = $this->sortAndUniqueDates($monthCandidates);
            
            // Apply BYSETPOS
            $monthCandidates = $this->applyBySetPos($rule, $monthCandidates);
            
            foreach ($monthCandidates as $date) {
                $candidate = $date->setTime(
                    (int) $start->format('H'),
                    (int) $start->format('i'),
                    (int) $start->format('s')
                );
                
                // Apply BY* filters
                if ($this->matchesByMonth($rule, $candidate)) {
                    if ($candidate >= $start) {
                        yield $candidate;
                    }
                }
            }
            
            $current = $current->modify("+{$interval} months");
            
            // Safety limit
            if ($end === null && $this->getSecondsDiff($start, $current) > 31536000 * 5) { // 5 years
                break;
            }
        }
    }

    /**
     * Generate YEARLY candidates
     */
    private function generateYearly(RRule $rule, \DateTimeImmutable $start, ?\DateTimeImmutable $end): \Generator
    {
        $current = $start;
        $interval = $rule->getInterval();
        $timezone = $start->getTimezone();
        
        while ($end === null || $current <= $end) {
            $year = (int) $current->format('Y');
            
            $yearCandidates = [];
            
            $byMonth = $rule->getByMonth();
            $byYearDay = $rule->getByYearDay();
            $byWeekNo = $rule->getByWeekNo();
            $byMonthDay = $rule->getByMonthDay();
            $byDay = $rule->getByDay();
            
            if ($byYearDay) {
                // Generate by year day
                foreach ($byYearDay as $yearDay) {
                    $date = $this->getYearDay($year, $yearDay, $timezone);
                    if ($date !== null) {
                        $yearCandidates[] = $date;
                    }
                }
            } elseif ($byWeekNo) {
                // Generate by week number
                foreach ($byWeekNo as $weekNo) {
                    $dates = $this->getWeekDates($year, $weekNo, $byDay, $timezone);
                    foreach ($dates as $date) {
                        $yearCandidates[] = $date;
                    }
                }
            } elseif ($byDay) {
                // Generate by weekday
                // If BYMONTH is NOT present, BYDAY applies to the whole year
                if (empty($byMonth)) {
                    foreach ($byDay as $dayInfo) {
                        $dayName = $dayInfo['day'];
                        $ordinal = $dayInfo['ordinal'];
                        
                        if ($ordinal !== null) {
                            $date = $this->getNthWeekdayOfYear($year, $dayName, $ordinal, $timezone);
                            if ($date !== null) {
                                $yearCandidates[] = $date;
                            }
                        } else {
                            $dates = $this->getAllWeekdaysInYear($year, $dayName, $timezone);
                            foreach ($dates as $date) {
                                $yearCandidates[] = $date;
                            }
                        }
                    }
                } else {
                    // BYMONTH is present, so BYDAY applies within those months
                    foreach ($byMonth as $month) {
                        foreach ($byDay as $dayInfo) {
                            $dayName = $dayInfo['day'];
                            $ordinal = $dayInfo['ordinal'];
                            
                            if ($ordinal !== null) {
                                $date = $this->getNthWeekdayOfMonth($year, $month, $dayName, $ordinal, $timezone);
                                if ($date !== null) {
                                    $yearCandidates[] = $date;
                                }
                            } else {
                                $dates = $this->getAllWeekdaysInMonth($year, $month, $dayName, $timezone);
                                foreach ($dates as $date) {
                                    $yearCandidates[] = $date;
                                }
                            }
                        }
                    }
                }
            } elseif ($byMonthDay) {
                // Generate by month day
                // If BYMONTH is not present, it defaults to all months? No, RFC says BYMONTH defaults to DTSTART month IF missing.
                // However, common sense for YEARLY + BYMONTHDAY is that it applies to the months specified in BYMONTH,
                // or if BYMONTH is missing, it applies to the START month?
                // RFC 5545: "If some or all of these BYxxx rule parts are not specified, they default to the corresponding values of the DTSTART property."
                // So if BYMONTH is missing, we use DTSTART's month.
                
                $months = !empty($byMonth) ? $byMonth : [(int) $start->format('n')];
                
                foreach ($months as $month) {
                    foreach ($byMonthDay as $day) {
                        $date = $this->getDayOfMonth($year, $month, $day, $timezone);
                        if ($date !== null) {
                            $yearCandidates[] = $date;
                        }
                    }
                }
            } else {
                // Default: same month and day as DTSTART
                // If BYMONTH is specified, we use that month, but same day?
                // Example: FREQ=YEARLY;BYMONTH=2. DTSTART=19970101. Result: 19970201.
                
                $months = !empty($byMonth) ? $byMonth : [(int) $start->format('n')];
                $day = (int) $start->format('j');
                
                foreach ($months as $month) {
                    $date = $this->getDayOfMonth($year, $month, $day, $timezone);
                    if ($date !== null) {
                        $yearCandidates[] = $date;
                    }
                }
            }
            
            // Sort and deduplicate
            $yearCandidates = $this->sortAndUniqueDates($yearCandidates);
            
            // Apply BYSETPOS
            $yearCandidates = $this->applyBySetPos($rule, $yearCandidates);
            
            foreach ($yearCandidates as $date) {
                $candidate = $date->setTime(
                    (int) $start->format('H'),
                    (int) $start->format('i'),
                    (int) $start->format('s')
                );
                
                if ($candidate >= $start) {
                    yield $candidate;
                }
            }
            
            $current = $current->modify("+{$interval} years");
            
            // Safety limit
            if ($end === null && $this->getSecondsDiff($start, $current) > 31536000 * 10) { // 10 years
                break;
            }
        }
    }

    /**
     * Apply BYHOUR, BYMINUTE, BYSECOND filters
     */
    private function applyTimeFilters(RRule $rule, \DateTimeImmutable $base): array
    {
        $candidates = [$base];
        
        $byHour = $rule->getByHour();
        $byMinute = $rule->getByMinute();
        $bySecond = $rule->getBySecond();
        
        if (!empty($byHour)) {
            $newCandidates = [];
            foreach ($candidates as $candidate) {
                foreach ($byHour as $hour) {
                    $newCandidates[] = $candidate->setTime($hour, (int) $candidate->format('i'), (int) $candidate->format('s'));
                }
            }
            $candidates = $newCandidates;
        }
        
        if (!empty($byMinute)) {
            $newCandidates = [];
            foreach ($candidates as $candidate) {
                foreach ($byMinute as $minute) {
                    $newCandidates[] = $candidate->setTime((int) $candidate->format('H'), $minute, (int) $candidate->format('s'));
                }
            }
            $candidates = $newCandidates;
        }
        
        if (!empty($bySecond)) {
            $newCandidates = [];
            foreach ($candidates as $candidate) {
                foreach ($bySecond as $second) {
                    $newCandidates[] = $candidate->setTime(
                        (int) $candidate->format('H'),
                        (int) $candidate->format('i'),
                        $second
                    );
                }
            }
            $candidates = $newCandidates;
        }
        
        return $candidates;
    }

    /**
     * Check if date matches BYMONTH filter
     */
    private function matchesByMonth(RRule $rule, \DateTimeImmutable $date): bool
    {
        $byMonth = $rule->getByMonth();
        if (empty($byMonth)) {
            return true;
        }
        
        $month = (int) $date->format('n');
        return in_array($month, $byMonth, true);
    }

    /**
     * Check if date matches BYMONTHDAY filter
     */
    private function matchesByMonthDay(RRule $rule, \DateTimeImmutable $date): bool
    {
        $byMonthDay = $rule->getByMonthDay();
        if (empty($byMonthDay)) {
            return true;
        }
        
        $day = (int) $date->format('j');
        $daysInMonth = (int) $date->format('t');
        
        foreach ($byMonthDay as $targetDay) {
            if ($targetDay > 0 && $day === $targetDay) {
                return true;
            }
            if ($targetDay < 0 && $day === ($daysInMonth + $targetDay + 1)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Apply BYSETPOS filter to candidates
     */
    private function applyBySetPos(RRule $rule, array $candidates): array
    {
        $bySetPos = $rule->getBySetPos();
        if (empty($bySetPos)) {
            return $candidates;
        }
        
        $result = [];
        $count = count($candidates);
        
        foreach ($bySetPos as $pos) {
            if ($pos > 0 && $pos <= $count) {
                $result[] = $candidates[$pos - 1];
            } elseif ($pos < 0 && abs($pos) <= $count) {
                $result[] = $candidates[$count + $pos];
            }
        }
        
        return $result;
    }

    /**
     * Get the nth weekday of a month (e.g., 2nd Tuesday)
     */
    private function getNthWeekdayOfMonth(int $year, int $month, string $dayName, int $n, \DateTimeZone $timezone): ?\DateTimeImmutable
    {
        $dayOfWeek = self::DAY_MAP[$dayName];
        $firstOfMonth = new \DateTimeImmutable("{$year}-{$month}-01", $timezone);
        $firstDayOfWeek = (int) $firstOfMonth->format('w');
        
        // Calculate days to first occurrence
        $daysToFirst = ($dayOfWeek - $firstDayOfWeek + 7) % 7;
        $firstOccurrence = 1 + $daysToFirst;
        
        // Calculate nth occurrence
        $targetDay = $firstOccurrence + (($n - 1) * 7);
        
        // Handle negative n (count from end)
        if ($n < 0) {
            $lastDayOfMonth = (int) $firstOfMonth->format('t');
            $lastOccurrence = $firstOccurrence;
            while ($lastOccurrence + 7 <= $lastDayOfMonth) {
                $lastOccurrence += 7;
            }
            $targetDay = $lastOccurrence + (($n + 1) * 7);
        }
        
        // Check if valid day for this month
        if ($targetDay < 1 || $targetDay > (int) $firstOfMonth->format('t')) {
            return null;
        }
        
        return new \DateTimeImmutable("{$year}-{$month}-{$targetDay}", $timezone);
    }

    /**
     * Get all occurrences of a weekday in a month
     */
    private function getAllWeekdaysInMonth(int $year, int $month, string $dayName, \DateTimeZone $timezone): array
    {
        $result = [];
        $dayOfWeek = self::DAY_MAP[$dayName];
        $firstOfMonth = new \DateTimeImmutable("{$year}-{$month}-01", $timezone);
        $firstDayOfWeek = (int) $firstOfMonth->format('w');
        
        $daysToFirst = ($dayOfWeek - $firstDayOfWeek + 7) % 7;
        $currentDay = 1 + $daysToFirst;
        $daysInMonth = (int) $firstOfMonth->format('t');
        
        while ($currentDay <= $daysInMonth) {
            $result[] = new \DateTimeImmutable("{$year}-{$month}-{$currentDay}", $timezone);
            $currentDay += 7;
        }
        
        return $result;
    }

    /**
     * Get a specific day of month, handling negative values
     */
    private function getDayOfMonth(int $year, int $month, int $day, \DateTimeZone $timezone): ?\DateTimeImmutable
    {
        $firstOfMonth = new \DateTimeImmutable("{$year}-{$month}-01", $timezone);
        $daysInMonth = (int) $firstOfMonth->format('t');
        
        if ($day > 0 && $day <= $daysInMonth) {
            return new \DateTimeImmutable("{$year}-{$month}-{$day}", $timezone);
        }
        
        if ($day < 0) {
            $actualDay = $daysInMonth + $day + 1;
            if ($actualDay >= 1) {
                return new \DateTimeImmutable("{$year}-{$month}-{$actualDay}", $timezone);
            }
        }
        
        return null;
    }

    /**
     * Get a date from a year day number
     */
    private function getYearDay(int $year, int $yearDay, \DateTimeZone $timezone): ?\DateTimeImmutable
    {
        if ($yearDay > 0) {
            return new \DateTimeImmutable("{$year}-01-01 +" . ($yearDay - 1) . ' days', $timezone);
        } else {
            $isLeap = (bool) (new \DateTimeImmutable("{$year}-01-01", $timezone))->format('L');
            $daysInYear = $isLeap ? 366 : 365;
            $day = $daysInYear + $yearDay + 1;
            return new \DateTimeImmutable("{$year}-01-01 +" . ($day - 1) . ' days', $timezone);
        }
    }

    /**
     * Get dates for a specific week number
     */
    private function getWeekDates(int $year, int $weekNo, array $byDay, \DateTimeZone $timezone): array
    {
        $result = [];
        
        // Get the first day of the first week
        $jan4 = new \DateTimeImmutable("{$year}-01-04", $timezone);
        $firstWeekStart = $jan4->modify('last monday');
        
        // Calculate target week start
        $targetWeekStart = $firstWeekStart->modify('+' . (($weekNo - 1) * 7) . ' days');
        
        // Handle negative week numbers
        if ($weekNo < 0) {
            $dec28 = new \DateTimeImmutable("{$year}-12-28", $timezone);
            $lastWeekStart = $dec28->modify('last monday');
            $targetWeekStart = $lastWeekStart->modify('+' . (($weekNo + 1) * 7) . ' days');
        }
        
        // Get all days in the week or filter by BYDAY
        for ($i = 0; $i < 7; $i++) {
            $date = $targetWeekStart->modify("+{$i} days");
            
            if (!empty($byDay)) {
                $dayName = array_search((int) $date->format('w'), self::DAY_MAP);
                $matches = false;
                foreach ($byDay as $dayInfo) {
                    if ($dayInfo['day'] === $dayName && $dayInfo['ordinal'] === null) {
                        $matches = true;
                        break;
                    }
                }
                if (!$matches) {
                    continue;
                }
            }
            
            if ((int) $date->format('Y') === $year) {
                $result[] = $date;
            }
        }
        
        return $result;
    }

    /**
     * Build a set of dates for fast lookup using full datetime precision
     */
    private function buildDateSet(array $dates): array
    {
        $set = [];
        foreach ($dates as $date) {
            $key = $date->format('Y-m-d\TH:i:s');
            $set[$key] = true;
        }
        return $set;
    }

    /**
     * Check if a date is in a set (matches on full datetime)
     */
    private function isDateInSet(\DateTimeInterface $date, array $set): bool
    {
        $key = $date->format('Y-m-d\TH:i:s');
        return isset($set[$key]);
    }

    /**
     * Determine the effective end date
     */
    private function determineEndDate(?\DateTimeImmutable $until, ?\DateTimeInterface $rangeEnd): ?\DateTimeImmutable
    {
        if ($until !== null && $rangeEnd !== null) {
            return $until < $rangeEnd ? $until : \DateTimeImmutable::createFromInterface($rangeEnd);
        }
        
        if ($until !== null) {
            return $until;
        }
        
        if ($rangeEnd !== null) {
            return \DateTimeImmutable::createFromInterface($rangeEnd);
        }
        
        return null;
    }

    /**
     * Get the nth weekday of a year (e.g., 20th Monday)
     */
    private function getNthWeekdayOfYear(int $year, string $dayName, int $n, \DateTimeZone $timezone): ?\DateTimeImmutable
    {
        $dayOfWeek = self::DAY_MAP[$dayName];
        $firstOfYear = new \DateTimeImmutable("{$year}-01-01", $timezone);
        $firstDayOfWeek = (int) $firstOfYear->format('w');
        
        // Calculate days to first occurrence
        $daysToFirst = ($dayOfWeek - $firstDayOfWeek + 7) % 7;
        $firstOccurrence = 1 + $daysToFirst;
        
        // Calculate nth occurrence
        $targetDayOfYear = $firstOccurrence + (($n - 1) * 7);
        
        // Handle negative n (count from end)
        if ($n < 0) {
            // Determine days in year
            $isLeap = (bool) $firstOfYear->format('L');
            $daysInYear = $isLeap ? 366 : 365;
            
            $lastOccurrence = $firstOccurrence;
            while ($lastOccurrence + 7 <= $daysInYear) {
                $lastOccurrence += 7;
            }
            $targetDayOfYear = $lastOccurrence + (($n + 1) * 7);
        }
        
        // Check if valid day for this year
        $isLeap = (bool) $firstOfYear->format('L');
        $daysInYear = $isLeap ? 366 : 365;
        
        if ($targetDayOfYear < 1 || $targetDayOfYear > $daysInYear) {
            return null;
        }
        
        // Convert day of year to date
        return $firstOfYear->modify("+" . ($targetDayOfYear - 1) . " days");
    }

    /**
     * Get all occurrences of a weekday in a year
     *
     * @return \DateTimeImmutable[]
     */
    private function getAllWeekdaysInYear(int $year, string $dayName, \DateTimeZone $timezone): array
    {
        $result = [];
        $dayOfWeek = self::DAY_MAP[$dayName];
        $firstOfYear = new \DateTimeImmutable("{$year}-01-01", $timezone);
        $firstDayOfWeek = (int) $firstOfYear->format('w');
        
        $daysToFirst = ($dayOfWeek - $firstDayOfWeek + 7) % 7;
        $currentDayOfYear = 1 + $daysToFirst;
        
        $isLeap = (bool) $firstOfYear->format('L');
        $daysInYear = $isLeap ? 366 : 365;
        
        while ($currentDayOfYear <= $daysInYear) {
            $result[] = $firstOfYear->modify("+" . ($currentDayOfYear - 1) . " days");
            $currentDayOfYear += 7;
        }
        
        return $result;
    }

    /**
     * Get difference in seconds between two dates
     */
    private function getSecondsDiff(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        return $end->getTimestamp() - $start->getTimestamp();
    }

    /**
     * Sort and remove duplicates from date array
     */
    private function sortAndUniqueDates(array $dates): array
    {
        // Sort by timestamp
        usort($dates, function (\DateTimeImmutable $a, \DateTimeImmutable $b) {
            return $a->getTimestamp() <=> $b->getTimestamp();
        });
        
        // Remove duplicates
        $unique = [];
        $last = null;
        foreach ($dates as $date) {
            if ($last === null || $date->format('Y-m-d') !== $last->format('Y-m-d')) {
                $unique[] = $date;
                $last = $date;
            }
        }
        
        return $unique;
    }
}