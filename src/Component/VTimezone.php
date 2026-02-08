<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;
use Icalendar\Value\TextValue;
use Icalendar\Value\DateTimeValue;

/**
 * VTIMEZONE component with timezone observance rules
 */
class VTimezone extends AbstractComponent
{
    /** @var array<array{time: string, offset: int, name: string}> */
    private array $transitions = [];

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

        foreach ($observances as $observance) {
            $dtstart = $observance->getProperty('DTSTART');
            $tzOffsetTo = $observance->getProperty('TZOFFSETTO');
            $tzName = $observance->getProperty('TZNAME');

            if ($dtstart === null || $tzOffsetTo === null) {
                continue;
            }

            $dtstartValue = $dtstart->getValue();
            $offsetValue = $tzOffsetTo->getValue();
            $offset = $this->parseUtcOffset($offsetValue->getRawValue());

            $name = $tzName !== null ? $tzName->getValue()->getRawValue() : 'UTC';

            $transitionTime = ($dtstartValue instanceof DateTimeValue) 
                ? $dtstartValue->getValue()->format('Y-m-d\TH:i:s')
                : $dtstartValue->getRawValue();

            $this->transitions[] = [
                'time' => $transitionTime,
                'offset' => $offset,
                'name' => $name
            ];
        }

        // Sort transitions by time ascending
        usort($this->transitions, fn($a, $b) => strcmp($a['time'], $b['time']));
    }

    /**
     * Get timezone offset at specific datetime
     */
    public function getOffsetAt(\DateTimeInterface $dt): int
    {
        if (empty($this->transitions)) {
            $this->buildTransitions();
        }

        $targetTime = $dt->format('Y-m-d\TH:i:s');
        $currentOffset = 0;

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
        if (empty($this->transitions)) {
            $this->buildTransitions();
        }

        $targetTime = $dt->format('Y-m-d\TH:i:s');
        $currentName = 'UTC';

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

    public function validate(): void
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

        // Validate each observance
        foreach ($observances as $observance) {
            if ($observance instanceof Standard || $observance instanceof Daylight) {
                $observance->validate();
            }
        }
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
