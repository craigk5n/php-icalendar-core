<?php

declare(strict_types=1);

namespace Icalendar\Recurrence;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;

/**
 * Generates recurrence instances from RRULE patterns
 *
 * Implements instance generation according to RFC 5545 §3.3.10.
 */
class RecurrenceGenerator
{
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
     * @param RRule $rule
     * @param DateTimeInterface $dtstart
     * @param DateTimeInterface|null $rangeEnd
     * @param array<DateTimeInterface> $exdates
     * @param array<DateTimeInterface> $rdates
     * @return Generator<int, DateTimeImmutable>
     */
    public function generate(
        RRule $rule,
        DateTimeInterface $dtstart,
        ?DateTimeInterface $rangeEnd = null,
        array $exdates = [],
        array $rdates = []
    ): Generator {
        $occurrenceCount = 0;
        $start = DateTimeImmutable::createFromInterface($dtstart);
        $timezone = $start->getTimezone();
        
        /** @var array<string, bool> $exdateStrings */
        $exdateStrings = [];
        foreach ($exdates as $ex) {
            $exdateStrings[DateTimeImmutable::createFromInterface($ex)->format('Ymd\THis')] = true;
        }
        
        /** @var DateTimeImmutable[] $sortedRdates */
        $sortedRdates = [];
        foreach ($rdates as $r) {
            $sortedRdates[] = DateTimeImmutable::createFromInterface($r)->setTimezone($timezone);
        }
        usort($sortedRdates, fn($a, $b) => $a <=> $b);
        
        $until = $rule->getUntil();
        if ($until !== null) {
            $until = $until->setTimezone($timezone);
        }
        
        $effectiveEnd = $rangeEnd ? DateTimeImmutable::createFromInterface($rangeEnd)->setTimezone($timezone) : null;
        if ($until !== null && ($effectiveEnd === null || $until < $effectiveEnd)) {
            $effectiveEnd = $until;
        }

        /** @var iterable<DateTimeImmutable> $candidates */
        $candidates = $this->generateCandidates($rule, $start, $effectiveEnd);
        
        $rdateIdx = 0;
        /** @var array<string, bool> $yielded */
        $yielded = [];

        foreach ($candidates as $candidate) {
            /** @var DateTimeImmutable $candidate */
            if ($candidate < $start) continue;
            
            // Yield RDATEs that come before this candidate
            while ($rdateIdx < count($sortedRdates) && $sortedRdates[$rdateIdx] < $candidate) {
                $rdate = $sortedRdates[$rdateIdx++];
                if ($this->shouldYield($rdate, $start, $effectiveEnd, $exdateStrings, $yielded, $rule, $occurrenceCount)) {
                    yield $rdate;
                    $yielded[$rdate->format('Ymd\THis')] = true;
                    $occurrenceCount++;
                }
            }

            if ($this->shouldYield($candidate, $start, $effectiveEnd, $exdateStrings, $yielded, $rule, $occurrenceCount)) {
                yield $candidate;
                $yielded[$candidate->format('Ymd\THis')] = true;
                $occurrenceCount++;
            }

            if ($rule->getCount() !== null && $occurrenceCount >= $rule->getCount()) break;
        }

        // Remaining RDATEs
        while ($rdateIdx < count($sortedRdates)) {
            $rdate = $sortedRdates[$rdateIdx++];
            if ($this->shouldYield($rdate, $start, $effectiveEnd, $exdateStrings, $yielded, $rule, $occurrenceCount)) {
                yield $rdate;
                $yielded[$rdate->format('Ymd\THis')] = true;
                $occurrenceCount++;
            }
            if ($rule->getCount() !== null && $occurrenceCount >= $rule->getCount()) break;
        }
    }

    /**
     * @param DateTimeImmutable $date
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable|null $end
     * @param array<string, bool> $exdates
     * @param array<string, bool> $yielded
     * @param RRule $rule
     * @param int $count
     * @return bool
     */
    private function shouldYield(
        DateTimeImmutable $date,
        DateTimeImmutable $start,
        ?DateTimeImmutable $end,
        array $exdates,
        array $yielded,
        RRule $rule,
        int $count
    ): bool {
        if ($date < $start) return false;
        if ($end !== null && $date > $end) return false;
        if (isset($exdates[$date->format('Ymd\THis')])) return false;
        if (isset($yielded[$date->format('Ymd\THis')])) return false;
        if ($rule->getCount() !== null && $count >= $rule->getCount()) return false;
        return true;
    }

    /**
     * @return Generator<int, DateTimeImmutable>
     */
    private function generateCandidates(RRule $rule, DateTimeImmutable $start, ?DateTimeImmutable $end): Generator
    {
        switch ($rule->getFreq()) {
            case 'SECONDLY': yield from $this->generateSecondly($rule, $start, $end); break;
            case 'MINUTELY': yield from $this->generateMinutely($rule, $start, $end); break;
            case 'HOURLY':   yield from $this->generateHourly($rule, $start, $end); break;
            case 'DAILY':    yield from $this->generateDaily($rule, $start, $end); break;
            case 'WEEKLY':   yield from $this->generateWeekly($rule, $start, $end); break;
            case 'MONTHLY':  yield from $this->generateMonthly($rule, $start, $end); break;
            case 'YEARLY':   yield from $this->generateYearly($rule, $start, $end); break;
        }
    }

    /**
     * @return Generator<int, DateTimeImmutable>
     */
    private function generateSecondly(RRule $rule, DateTimeImmutable $start, ?DateTimeImmutable $end): Generator
    {
        $curr = $start;
        $interval = $rule->getInterval();
        while ($end === null || $curr <= $end) {
            if ($this->matchesFilters($rule, $curr, $start)) yield $curr;
            $curr = $curr->modify("+{$interval} seconds");
            if ($end === null && $curr->getTimestamp() - $start->getTimestamp() > 315360000) break; // 10 years
        }
    }

    /**
     * @return Generator<int, DateTimeImmutable>
     */
    private function generateMinutely(RRule $rule, DateTimeImmutable $start, ?DateTimeImmutable $end): Generator
    {
        $curr = $start;
        $interval = $rule->getInterval();
        while ($end === null || $curr <= $end) {
            if ($this->matchesFilters($rule, $curr, $start)) yield $curr;
            $curr = $curr->modify("+{$interval} minutes");
            if ($end === null && $curr->getTimestamp() - $start->getTimestamp() > 315360000) break; // 10 years
        }
    }

    /**
     * @return Generator<int, DateTimeImmutable>
     */
    private function generateHourly(RRule $rule, DateTimeImmutable $start, ?DateTimeImmutable $end): Generator
    {
        $curr = $start;
        $interval = $rule->getInterval();
        while ($end === null || $curr <= $end) {
            if ($this->matchesFilters($rule, $curr, $start)) yield $curr;
            $curr = $curr->modify("+{$interval} hours");
            if ($end === null && $curr->getTimestamp() - $start->getTimestamp() > 315360000) break; // 10 years
        }
    }

    /**
     * @return Generator<int, DateTimeImmutable>
     */
    private function generateDaily(RRule $rule, DateTimeImmutable $start, ?DateTimeImmutable $end): Generator
    {
        $curr = $start;
        $interval = $rule->getInterval();
        while ($end === null || $curr <= $end) {
            if ($this->matchesFilters($rule, $curr, $start)) yield $curr;
            $curr = $curr->modify("+{$interval} days");
            if ($end === null && $curr->getTimestamp() - $start->getTimestamp() > 3153600000) break; // 100 years
        }
    }

    /**
     * @return Generator<int, DateTimeImmutable>
     */
    private function generateWeekly(RRule $rule, DateTimeImmutable $start, ?DateTimeImmutable $end): Generator
    {
        $interval = $rule->getInterval();
        $wkst = $rule->getWkst();
        /** @var int $weekStartDay */
        $weekStartDay = self::DAY_MAP[$wkst];
        
        $curr = $start;
        // Move to start of week
        $daysToStart = ((int)$curr->format('w') - $weekStartDay + 7) % 7;
        $curr = $curr->modify("-{$daysToStart} days");

        while ($end === null || $curr <= $end) {
            for ($i = 0; $i < 7; $i++) {
                $day = $curr->modify("+{$i} days");
                $day = $day->setTime((int)$start->format('H'), (int)$start->format('i'), (int)$start->format('s'));
                if ($day >= $start && ($end === null || $day <= $end)) {
                    if ($this->matchesFilters($rule, $day, $start)) yield $day;
                }
            }
            $curr = $curr->modify("+{$interval} weeks");
            if ($end === null && $curr->getTimestamp() - $start->getTimestamp() > 3153600000) break; // 100 years
        }
    }

    /**
     * @return Generator<int, DateTimeImmutable>
     */
    private function generateMonthly(RRule $rule, DateTimeImmutable $start, ?DateTimeImmutable $end): Generator
    {
        $curr = $start->modify('first day of this month');
        $interval = $rule->getInterval();

        while ($end === null || $curr <= $end) {
            $year = (int)($curr->format('Y'));
            $month = (int)($curr->format('n'));
            $daysInMonth = (int)($curr->format('t'));
            
            /** @var int[] $candidateDays */
            $candidateDays = [];
            
            if (!empty($rule->getByMonthDay())) {
                foreach ($rule->getByMonthDay() as $md) {
                    $d = ($md > 0) ? $md : ($daysInMonth + $md + 1);
                    if ($d >= 1 && $d <= $daysInMonth) $candidateDays[] = $d;
                }
            } elseif (!empty($rule->getByDay()) || !empty($rule->getBySetPos())) {
                for ($d = 1; $d <= $daysInMonth; $d++) $candidateDays[] = $d;
            } else {
                $candidateDays[] = (int)$start->format('j');
            }

            $candidateDays = array_unique($candidateDays);
            sort($candidateDays);

            /** @var DateTimeImmutable[] $validDates */
            $validDates = [];
            foreach ($candidateDays as $d) {
                $date = $curr->setDate($year, $month, $d)->setTime((int)$start->format('H'), (int)$start->format('i'), (int)$start->format('s'));
                if ($this->matchesFilters($rule, $date, $start)) {
                    $validDates[] = $date;
                }
            }

            $validDates = $this->applyBySetPos($rule, $validDates);
            foreach ($validDates as $date) {
                if ($date >= $start && ($end === null || $date <= $end)) yield $date;
            }

            $curr = $curr->modify("+{$interval} months");
            if ($end === null && $curr->getTimestamp() - $start->getTimestamp() > 3153600000) break; // 100 years
        }
    }

    /**
     * @return Generator<int, DateTimeImmutable>
     */
    private function generateYearly(RRule $rule, DateTimeImmutable $start, ?DateTimeImmutable $end): Generator
    {
        $curr = $start->modify('first day of January this year');
        $interval = $rule->getInterval();
        $timezone = $start->getTimezone();

        while ($end === null || $curr <= $end) {
            $year = (int)($curr->format('Y'));
            $isLeap = (bool)($curr->format('L'));
            $daysInYear = $isLeap ? 366 : 365;
            
            /** @var DateTimeImmutable[] $candidates */
            $candidates = [];
            /** @var int[] $months */
            $months = !empty($rule->getByMonth()) ? $rule->getByMonth() : [(int)$start->format('n')];

            $isExpanding = !empty($rule->getByWeekNo()) || !empty($rule->getByYearDay()) || !empty($rule->getByMonthDay()) || !empty($rule->getByDay()) || !empty($rule->getBySetPos());

            if (!empty($rule->getByWeekNo())) {
                foreach ($rule->getByWeekNo() as $wn) {
                    $wStart = $this->getIsoWeekStart($year, $wn, $timezone);
                    for ($i = 0; $i < 7; $i++) {
                        $d = $wStart->modify("+{$i} days");
                        if ((int)$d->format('Y') === $year) $candidates[] = $d;
                    }
                }
            } elseif (!empty($rule->getByYearDay())) {
                foreach ($rule->getByYearDay() as $yd) {
                    $d = ($yd > 0) ? $yd : ($daysInYear + $yd + 1);
                    if ($d >= 1 && $d <= $daysInYear) {
                        $candidates[] = $curr->modify("+" . ($d - 1) . " days");
                    }
                }
            } elseif ($isExpanding) {
                foreach ($months as $m) {
                    $mStart = $curr->setDate($year, $m, 1);
                    $dim = (int)($mStart->format('t'));
                    for ($d = 1; $d <= $dim; $d++) {
                        $candidates[] = $mStart->setDate($year, $m, $d);
                    }
                }
            } else {
                foreach ($months as $m) {
                    $candidates[] = $start->setDate($year, $m, (int)$start->format('j'));
                }
            }

            /** @var DateTimeImmutable[] $validDates */
            $validDates = [];
            foreach ($candidates as $date) {
                $date = $date->setTime((int)$start->format('H'), (int)$start->format('i'), (int)$start->format('s'));
                if ($this->matchesFilters($rule, $date, $start)) {
                    $validDates[] = $date;
                }
            }

            usort($validDates, fn($a, $b) => $a <=> $b);
            $validDates = $this->applyBySetPos($rule, $validDates);
            
            foreach ($validDates as $date) {
                if ($date >= $start && ($end === null || $date <= $end)) yield $date;
            }

            $curr = $curr->modify("+{$interval} years");
            if ($end === null && $curr->getTimestamp() - $start->getTimestamp() > 3153600000) break; // 100 years
        }
    }

    private function matchesFilters(RRule $rule, DateTimeImmutable $date, DateTimeImmutable $start): bool
    {
        if (!empty($rule->getByMonth()) && !in_array((int)$date->format('n'), $rule->getByMonth(), true)) return false;
        
        if (!empty($rule->getByWeekNo())) {
            if (!$this->matchesIsoWeek($date, $rule->getByWeekNo())) return false;
        }

        if (!empty($rule->getByYearDay())) {
            $yd = (int)($date->format('z')) + 1;
            $isLeap = (bool)($date->format('L'));
            $diy = $isLeap ? 366 : 365;
            $matches = false;
            foreach ($rule->getByYearDay() as $target) {
                $actual = ($target > 0) ? $target : ($diy + $target + 1);
                if ($yd === $actual) { $matches = true; break; }
            }
            if (!$matches) return false;
        }

        if (!empty($rule->getByMonthDay())) {
            $md = (int)($date->format('j'));
            $dim = (int)($date->format('t'));
            $matches = false;
            foreach ($rule->getByMonthDay() as $target) {
                $actual = ($target > 0) ? $target : ($dim + $target + 1);
                if ($md === $actual) { $matches = true; break; }
            }
            if (!$matches) return false;
        }

        if ($rule->getFreq() === 'WEEKLY' && empty($rule->getByDay()) && (int)$date->format('w') !== (int)$start->format('w')) {
            return false;
        }

        if (!empty($rule->getByDay()) && !$this->matchesByDay($rule, $date)) return false;

        return true;
    }

    private function matchesByDay(RRule $rule, DateTimeImmutable $date): bool
    {
        $dayName = strtoupper(substr($date->format('D'), 0, 2));
        $byDay = $rule->getByDay();
        
        foreach ($byDay as $dayInfo) {
            if ($dayInfo['day'] !== $dayName) continue;
            
            if ($dayInfo['ordinal'] === null) return true;
            
            if ($rule->getFreq() === 'MONTHLY' || (!empty($rule->getByMonth()) && $rule->getFreq() === 'YEARLY')) {
                $ordinal = $this->getDayOrdinalInMonth($date);
                if ($dayInfo['ordinal'] === $ordinal) return true;
                $negOrdinal = $this->getDayOrdinalInMonth($date, true);
                if ($dayInfo['ordinal'] === $negOrdinal) return true;
            } else {
                $ordinal = $this->getDayOrdinalInYear($date);
                if ($dayInfo['ordinal'] === $ordinal) return true;
                $negOrdinal = $this->getDayOrdinalInYear($date, true);
                if ($dayInfo['ordinal'] === $negOrdinal) return true;
            }
        }
        return false;
    }

    private function getDayOrdinalInMonth(DateTimeImmutable $date, bool $negative = false): int
    {
        $d = (int)($date->format('j'));
        if (!$negative) return (int)ceil($d / 7);
        $dim = (int)($date->format('t'));
        return -((int)ceil(($dim - $d + 1) / 7));
    }

    private function getDayOrdinalInYear(DateTimeImmutable $date, bool $negative = false): int
    {
        $yd = (int)($date->format('z')) + 1;
        if (!$negative) return (int)ceil($yd / 7);
        $diy = (int)($date->format('L')) ? 366 : 365;
        return -((int)ceil(($diy - $yd + 1) / 7));
    }

    /**
     * @param DateTimeImmutable $date
     * @param int[] $byWeekNo
     * @return bool
     */
    private function matchesIsoWeek(DateTimeImmutable $date, array $byWeekNo): bool
    {
        $year = (int)($date->format('o'));
        $week = (int)($date->format('W'));
        foreach ($byWeekNo as $wn) {
            if ($wn > 0 && $week === $wn) return true;
            if ($wn < 0) {
                $lastWeek = (int)((new DateTimeImmutable())->setISODate($year, 53)->format('W')) === 53 ? 53 : 52;
                if ($week === ($lastWeek + $wn + 1)) return true;
            }
        }
        return false;
    }

    private function getIsoWeekStart(int $year, int $weekNo, DateTimeZone $tz): DateTimeImmutable
    {
        $date = new DateTimeImmutable('now', $tz);
        if ($weekNo > 0) return $date->setISODate($year, $weekNo);
        $lastWeek = (int)((new DateTimeImmutable())->setISODate($year, 53)->format('W')) === 53 ? 53 : 52;
        return $date->setISODate($year, $lastWeek + $weekNo + 1);
    }

    /**
     * @param RRule $rule
     * @param DateTimeImmutable[] $dates
     * @return DateTimeImmutable[]
     */
    private function applyBySetPos(RRule $rule, array $dates): array
    {
        $bySetPos = $rule->getBySetPos();
        if (empty($bySetPos) || empty($dates)) return $dates;
        $result = [];
        $count = count($dates);
        foreach ($bySetPos as $pos) {
            if ($pos > 0 && $pos <= $count) $result[] = $dates[$pos - 1];
            elseif ($pos < 0 && abs($pos) <= $count) $result[] = $dates[$count + $pos];
        }
        usort($result, fn($a, $b) => $a <=> $b);
        /** @var array<string, DateTimeImmutable> $unique */
        $unique = [];
        foreach ($result as $d) {
            $key = $d->format('Ymd\THis');
            if (!isset($unique[$key])) {
                $unique[$key] = $d;
            }
        }
        return array_values($unique);
    }
}