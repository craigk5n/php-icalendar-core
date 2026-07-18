<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Parser\ValueParser\DateTimeParser;
use Icalendar\Property\GenericProperty;
use Icalendar\Property\PropertyInterface;
use Icalendar\Recurrence\RecurrenceGenerator;
use Icalendar\Recurrence\RRuleParser;
use Icalendar\Value\TextValue;
use Icalendar\Value\DateTimeValue;

/**
 * VTIMEZONE component with timezone observance rules
 */
class VTimezone extends AbstractComponent
{
    /**
     * How far past the latest observance start the transition table is expanded
     * when no explicit end is supplied. Recurring observances are usually
     * unbounded, so expansion needs a horizon; queries beyond it extend the
     * table on demand via ensureTransitionsCover().
     */
    private const DEFAULT_HORIZON_YEARS = 10;

    /** @var array<array{time: string, offset: int, name: string}> */
    private array $transitions = [];

    /** Instant the current table is expanded through, or null if not yet built. */
    private ?\DateTimeImmutable $builtThrough = null;

    /**
     * Offset in effect before the first transition. RFC 5545 records it as the
     * earliest observance's TZOFFSETFROM; defaulting to 0 claimed UTC.
     */
    private int $initialOffset = 0;

    private string $initialName = 'UTC';

    #[\Override]
    public function getName(): string
    {
        return 'VTIMEZONE';
    }

    public function setTzId(string $tzid): self
    {
        $this->removeProperty('TZID');
        $this->addProperty(new GenericProperty('TZID', new TextValue($tzid)));
        return $this;
    }

    public function setLastModified(\DateTimeInterface $lastModified): self
    {
        $this->removeProperty('LAST-MODIFIED');
        $this->addProperty(new GenericProperty('LAST-MODIFIED', new DateTimeValue($lastModified)));
        return $this;
    }

    public function setTzUrl(string $url): self
    {
        $this->removeProperty('TZURL');
        $this->addProperty(new GenericProperty('TZURL', new TextValue($url)));
        return $this;
    }

    public function addStandard(Standard $standard): self
    {
        $this->addComponent($standard);
        return $this;
    }

    public function addDaylight(Daylight $daylight): self
    {
        $this->addComponent($daylight);
        return $this;
    }

    /**
     * Build transition table from observances
     */
    public function buildTransitions(?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null): void
    {
        $this->transitions = [];

        $observances = array_merge(
            $this->getComponents('STANDARD'),
            $this->getComponents('DAYLIGHT')
        );

        $starts = [];
        foreach ($observances as $observance) {
            $dtstart = $observance->getProperty('DTSTART');
            if ($dtstart !== null) {
                $parsed = $this->observanceStart($dtstart);
                if ($parsed !== null) {
                    $starts[] = $parsed;
                }
            }
        }

        $horizon = $this->resolveHorizon($starts, $end);

        $earliest = null;
        $earliestFrom = null;

        foreach ($observances as $observance) {
            $dtstart = $observance->getProperty('DTSTART');
            $tzOffsetTo = $observance->getProperty('TZOFFSETTO');
            $tzName = $observance->getProperty('TZNAME');

            if ($dtstart === null || $tzOffsetTo === null) {
                continue;
            }

            $observanceStart = $this->observanceStart($dtstart);
            if ($observanceStart === null) {
                continue;
            }

            $offset = $this->parseUtcOffset($tzOffsetTo->getValue()->getRawValue());
            $name = $tzName !== null ? $tzName->getValue()->getRawValue() : 'UTC';

            // Track the earliest observance so the pre-first-transition offset
            // can come from its TZOFFSETFROM rather than defaulting to UTC.
            if ($earliest === null || $observanceStart < $earliest) {
                $earliest = $observanceStart;
                $tzOffsetFrom = $observance->getProperty('TZOFFSETFROM');
                $earliestFrom = $tzOffsetFrom !== null
                    ? $this->parseUtcOffset($tzOffsetFrom->getValue()->getRawValue())
                    : null;
            }

            foreach ($this->observanceOccurrences($observance, $observanceStart, $start, $horizon) as $occurrence) {
                $this->transitions[] = [
                    'time' => $occurrence->format('Y-m-d\TH:i:s'),
                    'offset' => $offset,
                    'name' => $name,
                ];
            }
        }

        // Sort transitions by time ascending
        usort($this->transitions, fn($a, $b) => strcmp($a['time'], $b['time']));

        $this->resolveInitialOffset($earliestFrom, $observances);
        $this->builtThrough = $horizon;
    }

    /**
     * Every transition an observance contributes: its DTSTART, plus each RRULE
     * occurrence up to the horizon.
     *
     * Ignoring RRULE was the defect behind #34 -- the table described a single
     * year, so any later date inherited whichever transition sorted last.
     *
     * @return list<\DateTimeImmutable>
     */
    private function observanceOccurrences(
        ComponentInterface $observance,
        \DateTimeImmutable $observanceStart,
        ?\DateTimeInterface $rangeStart,
        \DateTimeImmutable $horizon
    ): array {
        $rrule = $observance->getProperty('RRULE');
        if ($rrule === null) {
            return $this->withinRange([$observanceStart], $rangeStart, $horizon);
        }

        try {
            $rule = (new RRuleParser())->parse($rrule->getValue()->getRawValue());
            $occurrences = iterator_to_array(
                (new RecurrenceGenerator())->generate($rule, $observanceStart, $horizon),
                false
            );
        } catch (\Exception) {
            // An unparseable RRULE must not cost us the observance itself: fall
            // back to the single literal transition rather than dropping it.
            return $this->withinRange([$observanceStart], $rangeStart, $horizon);
        }

        // RFC 5545 §3.6.5: DTSTART "gives the effective onset" -- it is itself a
        // transition, and RRULE defines the subsequent ones. Real calendars carry
        // observances whose DTSTART does not match their own rule pattern (the
        // in-tree fixture starts on the last Sunday of October under a BYDAY=1SU
        // rule), and generating only rule matches silently dropped that first
        // onset along with the whole first year.
        array_unshift($occurrences, $observanceStart);

        return $this->deduplicate($this->withinRange($occurrences, $rangeStart, $horizon));
    }

    /**
     * DTSTART is prepended unconditionally, so it may duplicate a rule match.
     *
     * @param list<\DateTimeImmutable> $occurrences
     * @return list<\DateTimeImmutable>
     */
    private function deduplicate(array $occurrences): array
    {
        $seen = [];
        $unique = [];
        foreach ($occurrences as $occurrence) {
            $key = $occurrence->format('Y-m-d\TH:i:s');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $occurrence;
        }

        return $unique;
    }

    /**
     * @param list<\DateTimeImmutable> $occurrences
     * @return list<\DateTimeImmutable>
     */
    private function withinRange(array $occurrences, ?\DateTimeInterface $rangeStart, \DateTimeImmutable $horizon): array
    {
        $kept = [];
        foreach ($occurrences as $occurrence) {
            if ($rangeStart !== null && $occurrence < $rangeStart) {
                continue;
            }
            if ($occurrence > $horizon) {
                continue;
            }
            $kept[] = $occurrence;
        }

        return $kept;
    }

    /**
     * @param list<\DateTimeImmutable> $starts
     */
    private function resolveHorizon(array $starts, ?\DateTimeInterface $end): \DateTimeImmutable
    {
        if ($end !== null) {
            return \DateTimeImmutable::createFromInterface($end);
        }

        $latest = null;
        foreach ($starts as $candidate) {
            if ($latest === null || $candidate > $latest) {
                $latest = $candidate;
            }
        }

        $base = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($latest !== null && $latest > $base) {
            $base = $latest;
        }

        return $base->modify('+' . self::DEFAULT_HORIZON_YEARS . ' years');
    }

    /**
     * The offset before the first transition is the earliest observance's
     * TZOFFSETFROM. Its name is not stated directly, so it is taken from
     * whichever observance switches *to* that offset, which for the usual
     * standard/daylight pair is the other one.
     *
     * @param array<int, ComponentInterface> $observances
     */
    private function resolveInitialOffset(?int $earliestFrom, array $observances): void
    {
        if ($earliestFrom === null) {
            $this->initialOffset = 0;
            $this->initialName = 'UTC';
            return;
        }

        $this->initialOffset = $earliestFrom;
        $this->initialName = 'UTC';

        foreach ($observances as $observance) {
            $tzOffsetTo = $observance->getProperty('TZOFFSETTO');
            $tzName = $observance->getProperty('TZNAME');
            if ($tzOffsetTo === null || $tzName === null) {
                continue;
            }

            if ($this->parseUtcOffset($tzOffsetTo->getValue()->getRawValue()) === $earliestFrom) {
                $this->initialName = $tzName->getValue()->getRawValue();
                return;
            }
        }
    }

    /**
     * An observance DTSTART, whatever shape it was stored in.
     *
     * Values set through setDtStart() arrive as a DateTimeValue, while parsed
     * ones arrive as the raw iCal string. The lookup compares 'Y-m-d\TH:i:s',
     * so a raw '20051030T020000Z' never matched anything and every parsed
     * VTIMEZONE resolved to offset 0 -- recurring or not.
     */
    private function observanceStart(PropertyInterface $dtstart): ?\DateTimeImmutable
    {
        $value = $dtstart->getValue();

        if ($value instanceof DateTimeValue) {
            return \DateTimeImmutable::createFromInterface($value->getValue());
        }

        $raw = $value->getRawValue();
        if ($raw === '') {
            return null;
        }

        try {
            return (new DateTimeParser())->parse($raw);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Recurring observances are unbounded, so the table is expanded to a
     * horizon. A query past it rebuilds far enough to answer.
     */
    private function ensureTransitionsCover(\DateTimeInterface $dt): void
    {
        if ($this->transitions !== [] && $this->builtThrough !== null && $dt <= $this->builtThrough) {
            return;
        }

        $target = \DateTimeImmutable::createFromInterface($dt);
        $this->buildTransitions(null, $target->modify('+1 year'));
    }

    /**
     * Get timezone offset at specific datetime
     */
    public function getOffsetAt(\DateTimeInterface $dt): int
    {
        $this->ensureTransitionsCover($dt);

        $targetTime = $dt->format('Y-m-d\TH:i:s');
        $currentOffset = $this->initialOffset;

        foreach ($this->transitions as $transition) {
            if ($transition['time'] <= $targetTime) {
                $currentOffset = $transition['offset'];
            } else {
                break;
            }
        }

        return $currentOffset;
    }

    /**
     * Get timezone abbreviation at specific datetime
     */
    public function getAbbreviationAt(\DateTimeInterface $dt): string
    {
        $this->ensureTransitionsCover($dt);

        $targetTime = $dt->format('Y-m-d\TH:i:s');
        $currentName = $this->initialName;

        foreach ($this->transitions as $transition) {
            if ($transition['time'] <= $targetTime) {
                $currentName = $transition['name'];
            } else {
                break;
            }
        }

        return $currentName;
    }

    /**
     * Map TZID to PHP DateTimeZone when possible
     */
    public function toPhpDateTimeZone(): ?\DateTimeZone
    {
        $tzidProp = $this->getProperty('TZID');
        if ($tzidProp === null) {
            return null;
        }

        $tzid = $tzidProp->getValue()->getRawValue();

        if ($tzid === '') {
            return null;
        }

        try {
            return new \DateTimeZone($tzid);
        } catch (\Exception) {
            return null;
        }
    }

    #[\Override]
    protected function validateSelf(): void
    {
        $tzid = $this->getProperty('TZID');
        if ($tzid === null) {
            throw new ValidationException('VTIMEZONE component missing required TZID property', ValidationException::ERR_TIMEZONE_MISSING_TZID);
        }

        $observances = array_merge(
            $this->getComponents('STANDARD'),
            $this->getComponents('DAYLIGHT')
        );

        if (empty($observances)) {
            throw new ValidationException('VTIMEZONE component requires at least one STANDARD or DAYLIGHT sub-component', ValidationException::ERR_TIMEZONE_MISSING_OBSERVANCE);
        }

        // Each observance is validated by AbstractComponent::validate()'s descent.
    }

    private function parseUtcOffset(string $offset): int
    {
        if (!preg_match('/^([+-])(\d{2})(\d{2})(\d{2})?$/', $offset, $matches)) {
            return 0;
        }

        $sign = $matches[1] === '+' ? 1 : -1;
        $hours = (int)$matches[2];
        $minutes = (int)$matches[3];
        $seconds = isset($matches[4]) ? (int)$matches[4] : 0;

        return $sign * (($hours * 3600) + ($minutes * 60) + $seconds);
    }

    /**
     * Get all transitions
     *
     * @return array<array{time: string, offset: int, name: string}>
     */
    public function getTransitions(): array
    {
        if (empty($this->transitions)) {
            $this->buildTransitions();
        }
        return $this->transitions;
    }
}
