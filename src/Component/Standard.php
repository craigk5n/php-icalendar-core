<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Component\Traits\UtcOffsetFormatterTrait;
use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;
use Icalendar\Value\TextValue;
use Icalendar\Value\DateTimeValue;

/**
 * STANDARD observance component for VTIMEZONE
 */
class Standard extends AbstractComponent
{
    use UtcOffsetFormatterTrait;

    #[\Override]
    public function getName(): string
    {
        return 'STANDARD';
    }

    public function setDtStart(\DateTimeInterface $dtstart): self
    {
        $this->removeProperty('DTSTART');
        $this->addProperty(new GenericProperty('DTSTART', new DateTimeValue($dtstart)));
        return $this;
    }

    public function setTzOffsetTo(int $offset): self
    {
        $offsetStr = $this->formatUtcOffset($offset);
        $this->removeProperty('TZOFFSETTO');
        $this->addProperty(new GenericProperty('TZOFFSETTO', new TextValue($offsetStr)));
        return $this;
    }

    public function setTzOffsetFrom(int $offset): self
    {
        $offsetStr = $this->formatUtcOffset($offset);
        $this->removeProperty('TZOFFSETFROM');
        $this->addProperty(new GenericProperty('TZOFFSETFROM', new TextValue($offsetStr)));
        return $this;
    }

    public function setTzName(string $name): self
    {
        $this->removeProperty('TZNAME');
        $this->addProperty(new GenericProperty('TZNAME', new TextValue($name)));
        return $this;
    }

    public function validate(): void
    {
        $dtstart = $this->getProperty('DTSTART');
        if ($dtstart === null) {
            throw new ValidationException('STANDARD component missing required DTSTART property', ValidationException::ERR_TZ_OBSERVANCE_MISSING_DTSTART);
        }

        $tzOffsetTo = $this->getProperty('TZOFFSETTO');
        if ($tzOffsetTo === null) {
            throw new ValidationException('STANDARD component missing required TZOFFSETTO property', ValidationException::ERR_TZ_OBSERVANCE_MISSING_TZOFFSETTO);
        }

        $tzOffsetFrom = $this->getProperty('TZOFFSETFROM');
        if ($tzOffsetFrom === null) {
            throw new ValidationException('STANDARD component missing required TZOFFSETFROM property', ValidationException::ERR_TZ_OBSERVANCE_MISSING_TZOFFSETFROM);
        }
    }

}
