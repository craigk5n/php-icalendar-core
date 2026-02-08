<?php

declare(strict_types=1);

namespace Icalendar\Recurrence;

use DateTimeImmutable;
use DateTimeInterface;
use DateInterval;
use InvalidArgumentException;
use Icalendar\Component\ComponentInterface;
use Icalendar\Parser\ValueParser\DateParser;
use Icalendar\Parser\ValueParser\DateTimeParser;
use Icalendar\Parser\ValueParser\DurationParser;

/**
 * Service for expanding recurrence rules (RRULE, EXDATE, RDATE) on calendar components.
 *
 * Bridges the gap between the low-level RecurrenceGenerator and the component layer.
 */
class RecurrenceExpander
{
    private RecurrenceGenerator $generator;
    private DateTimeParser $dateTimeParser;
    private DateParser $dateParser;
    private DurationParser $durationParser;
    private RRuleParser $rruleParser;

    public function __construct(?RecurrenceGenerator $generator = null)
    {
        $this->generator = $generator ?? new RecurrenceGenerator();
        $this->dateTimeParser = new DateTimeParser();
        $this->dateParser = new DateParser();
        $this->durationParser = new DurationParser();
        $this->rruleParser = new RRuleParser();
    }

    /**
     * Parse DTSTART property from component.
     *
     * @throws InvalidArgumentException if DTSTART is missing.
     */
    private function parseDtStart(ComponentInterface $component): DateTimeImmutable
    {
        $prop = $component->getProperty('DTSTART');
        if ($prop === null) {
            throw new InvalidArgumentException('Component is missing DTSTART property');
        }

        $value = $prop->getValue()->getRawValue();
        $params = $prop->getParameters();

        if (isset($params['VALUE']) && strtoupper($params['VALUE']) === 'DATE') {
            return $this->dateParser->parse($value, $params);
        }

        return $this->dateTimeParser->parse($value, $params);
    }

    /**
     * Parse duration from DTEND, DURATION, or DUE.
     */
    private function parseDuration(ComponentInterface $component, DateTimeImmutable $dtstart): ?DateInterval
    {
        // Try DTEND first
        $dtendProp = $component->getProperty('DTEND');
        if ($dtendProp !== null) {
            $value = $dtendProp->getValue()->getRawValue();
            $params = $dtendProp->getParameters();
            $dtend = (isset($params['VALUE']) && strtoupper($params['VALUE']) === 'DATE')
                ? $this->dateParser->parse($value, $params)
                : $this->dateTimeParser->parse($value, $params);
            
            return $dtstart->diff($dtend);
        }

        // Try DURATION
        $durationProp = $component->getProperty('DURATION');
        if ($durationProp !== null) {
            return $this->durationParser->parse($durationProp->getValue()->getRawValue());
        }

        // Try DUE (VTODO only)
        $dueProp = $component->getProperty('DUE');
        if ($dueProp !== null) {
            $value = $dueProp->getValue()->getRawValue();
            $params = $dueProp->getParameters();
            $due = (isset($params['VALUE']) && strtoupper($params['VALUE']) === 'DATE')
                ? $this->dateParser->parse($value, $params)
                : $this->dateTimeParser->parse($value, $params);

            return $dtstart->diff($due);
        }

        return null;
    }

    /**
     * Parse all RRULE properties.
     *
     * @return RRule[]
     */
    private function parseRrules(ComponentInterface $component): array
    {
        $rrules = [];
        $props = $this->getAllProperties($component, 'RRULE');
        foreach ($props as $prop) {
            $rrules[] = $this->rruleParser->parse($prop->getValue()->getRawValue());
        }
        return $rrules;
    }

    /**
     * Parse all EXDATE properties.
     *
     * @return array<array{date: DateTimeImmutable, isDate: bool}>
     */
    private function parseExdates(ComponentInterface $component): array
    {
        return $this->parseDateList($component, 'EXDATE');
    }

    /**
     * Parse all RDATE properties.
     *
     * @return array<array{date: DateTimeImmutable, isDate: bool}>
     */
    private function parseRdates(ComponentInterface $component): array
    {
        return $this->parseDateList($component, 'RDATE');
    }

    /**
     * Helper to parse properties that contain a comma-separated list of dates/date-times.
     *
     * @return array<array{date: DateTimeImmutable, isDate: bool}>
     */
    private function parseDateList(ComponentInterface $component, string $propertyName): array
    {
        $dates = [];
        $props = $this->getAllProperties($component, $propertyName);
        foreach ($props as $prop) {
            $rawValue = $prop->getValue()->getRawValue();
            $params = $prop->getParameters();
            $isDate = isset($params['VALUE']) && strtoupper($params['VALUE']) === 'DATE';
            
            $values = explode(',', $rawValue);
            foreach ($values as $value) {
                $value = trim($value);
                if ($value === '') continue;
                
                $dates[] = [
                    'date' => $isDate 
                        ? $this->dateParser->parse($value, $params)
                        : $this->dateTimeParser->parse($value, $params),
                    'isDate' => $isDate
                ];
            }
        }
        return $dates;
    }

    /**
     * Expand the recurrence rules of a component into a generator of Occurrence objects.
     *
     * @return \Generator<Occurrence>
     */
    public function expand(ComponentInterface $component, ?DateTimeInterface $rangeEnd = null): \Generator
    {
        $dtstart = $this->parseDtStart($component);
        $duration = $this->parseDuration($component, $dtstart);
        $rrules = $this->parseRrules($component);
        $exdates = $this->parseExdates($component);
        $rdates = $this->parseRdates($component);

        // Validate bounds
        foreach ($rrules as $rule) {
            if (!$rule->hasCount() && !$rule->hasUntil() && $rangeEnd === null) {
                throw new InvalidArgumentException('Unbounded recurrence rule requires a rangeEnd');
            }
        }

        // Build EXDATE hashset for fast lookup
        $exdateSet = [];
        $exdateDates = []; // For VALUE=DATE matching
        foreach ($exdates as $exdateInfo) {
            $exdate = $exdateInfo['date'];
            if ($exdateInfo['isDate']) {
                $exdateDates[$exdate->format('Y-m-d')] = true;
            } else {
                $exdateSet[$exdate->format('Y-m-d\TH:i:s')] = true;
            }
        }

        // RDATE occurrences
        $rdateOccurrences = [];
        foreach ($rdates as $rdateInfo) {
            $rdate = $rdateInfo['date'];
            if ($this->isExcluded($rdate, $exdateSet, $exdateDates)) {
                continue;
            }
            $rdateOccurrences[] = new Occurrence($rdate, $this->computeEnd($rdate, $duration), true);
        }

        // Sort RDATE occurrences chronologically
        usort($rdateOccurrences, fn($a, $b) => $a->getStart() <=> $b->getStart());

        // Generate RRULE occurrences
        $rruleGenerator = $this->generator;
        $allGenerators = [];
        foreach ($rrules as $rule) {
            $allGenerators[] = $rruleGenerator->generate($rule, $dtstart, $rangeEnd, [], []);
        }

        $rruleStream = $this->mergeGenerators($allGenerators);

        // Combine and yield
        $yieldedTimestamps = [];

        foreach ($rruleStream as $date) {
            if ($this->isExcluded($date, $exdateSet, $exdateDates)) {
                continue;
            }

            $ts = $date->getTimestamp();
            if (isset($yieldedTimestamps[$ts])) {
                continue;
            }

            // Yield any RDATEs that come before this RRULE date
            while (!empty($rdateOccurrences) && $rdateOccurrences[0]->getStart() < $date) {
                $rdateOcc = array_shift($rdateOccurrences);
                if (!isset($yieldedTimestamps[$rdateOcc->getStart()->getTimestamp()])) {
                    yield $rdateOcc;
                    $yieldedTimestamps[$rdateOcc->getStart()->getTimestamp()] = true;
                }
            }

            // Yield RRULE date
            yield new Occurrence($date, $this->computeEnd($date, $duration), false);
            $yieldedTimestamps[$ts] = true;
        }

        // No-RRULE case or leftover RDATEs
        if (empty($rrules) && !isset($yieldedTimestamps[$dtstart->getTimestamp()])) {
            if (!$this->isExcluded($dtstart, $exdateSet, $exdateDates)) {
                yield new Occurrence($dtstart, $this->computeEnd($dtstart, $duration), false);
                $yieldedTimestamps[$dtstart->getTimestamp()] = true;
            }
        }

        // Yield remaining RDATEs
        while (!empty($rdateOccurrences)) {
            $rdateOcc = array_shift($rdateOccurrences);
            if (!isset($yieldedTimestamps[$rdateOcc->getStart()->getTimestamp()])) {
                yield $rdateOcc;
                $yieldedTimestamps[$rdateOcc->getStart()->getTimestamp()] = true;
            }
        }
    }

    /**
     * Expand the recurrence rules of a component into an array of Occurrence objects.
     *
     * @return Occurrence[]
     */
    public function expandToArray(ComponentInterface $component, ?DateTimeInterface $rangeEnd = null): array
    {
        return iterator_to_array($this->expand($component, $rangeEnd), false);
    }

    /**
     * Merge multiple sorted generators and deduplicate.
     *
     * @param iterable<\Generator<mixed, DateTimeImmutable>> $generators
     * @return \Generator<int, DateTimeImmutable>
     */
    private function mergeGenerators(iterable $generators): \Generator
    {
        /** @var array<int, \Generator<mixed, DateTimeImmutable>> $generatorsArray */
        $generatorsArray = [];
        foreach ($generators as $gen) {
            $generatorsArray[] = $gen;
        }

        if (count($generatorsArray) === 0) {
            return;
        }

        if (count($generatorsArray) === 1) {
            $i = 0;
            foreach ($generatorsArray[0] as $value) {
                yield $i++ => $value;
            }
            return;
        }

        /** @var array<int, DateTimeImmutable> $buffer */
        $buffer = [];
        foreach ($generatorsArray as $i => $gen) {
            if ($gen->valid()) {
                /** @var DateTimeImmutable $current */
                $current = $gen->current();
                $buffer[$i] = $current;
            }
        }

        $yieldKey = 0;
        while (!empty($buffer)) {
            // Find earliest date in buffer
            /** @var int|null $earliestIdx */
            $earliestIdx = null;
            $earliestDate = null;

            foreach ($buffer as $i => $date) {
                /** @var DateTimeImmutable|null $date */
                if ($earliestDate === null || ($date !== null && $date < $earliestDate)) {
                    $earliestDate = $date;
                    $earliestIdx = $i;
                }
            }

            if ($earliestIdx === null || $earliestDate === null) {
                break;
            }

            yield $yieldKey++ => $earliestDate;

            // Advance the generator that yielded
            $generatorsArray[$earliestIdx]->next();
            if ($generatorsArray[$earliestIdx]->valid()) {
                /** @var DateTimeImmutable $next */
                $next = $generatorsArray[$earliestIdx]->current();
                $buffer[$earliestIdx] = $next;
            } else {
                unset($buffer[$earliestIdx]);
            }
        }
    }

    /**
     * @param array<string, bool> $exdateSet
     * @param array<string, bool> $exdateDates
     */
    private function isExcluded(DateTimeImmutable $date, array $exdateSet, array $exdateDates): bool
    {
        if (isset($exdateSet[$date->format('Y-m-d\TH:i:s')])) {
            return true;
        }
        if (isset($exdateDates[$date->format('Y-m-d')])) {
            return true;
        }
        return false;
    }

    private function computeEnd(DateTimeImmutable $start, ?DateInterval $duration): ?DateTimeImmutable
    {
        if ($duration === null) {
            return null;
        }
        return $start->add($duration);
    }

    /**
     * Get all properties with a given name from a component.
     * Handles cases where the component might not be an AbstractComponent.
     *
     * @return \Icalendar\Property\PropertyInterface[]
     */
    private function getAllProperties(ComponentInterface $component, string $name): array
    {
        if (method_exists($component, 'getAllProperties')) {
            return $component->getAllProperties($name);
        }

        return array_values(array_filter(
            $component->getProperties(),
            fn($p) => $p->getName() === $name
        ));
    }
}
