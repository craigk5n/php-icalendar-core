<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * Root VCALENDAR component
 *
 * The VCALENDAR component is the top-level container for all iCalendar data.
 * It must contain at least one of each required property.
 */
class VCalendar extends AbstractComponent
{
    public const ERR_MISSING_PRODID = 'ICAL-COMP-001';
    public const ERR_MISSING_VERSION = 'ICAL-COMP-002';

    public function getName(): string
    {
        return 'VCALENDAR';
    }

    public function setProductId(string $prodId): self
    {
        $this->removeProperty('PRODID');
        $this->addProperty(GenericProperty::create('PRODID', $prodId));
        return $this;
    }

    public function getProductId(): ?string
    {
        $prop = $this->getProperty('PRODID');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setVersion(string $version): self
    {
        $this->removeProperty('VERSION');
        $this->addProperty(GenericProperty::create('VERSION', $version));
        return $this;
    }

    public function getVersion(): ?string
    {
        $prop = $this->getProperty('VERSION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setCalscale(string $calscale): self
    {
        $this->removeProperty('CALSCALE');
        $this->addProperty(GenericProperty::create('CALSCALE', $calscale));
        return $this;
    }

    public function getCalscale(): ?string
    {
        $prop = $this->getProperty('CALSCALE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setMethod(string $method): self
    {
        $this->removeProperty('METHOD');
        $this->addProperty(GenericProperty::create('METHOD', $method));
        return $this;
    }

    public function getMethod(): ?string
    {
        $prop = $this->getProperty('METHOD');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function validate(): void
    {
        if ($this->getProperty('PRODID') === null) {
            throw new ValidationException(
                'VCALENDAR must contain a PRODID property',
                self::ERR_MISSING_PRODID
            );
        }

        if ($this->getProperty('VERSION') === null) {
            throw new ValidationException(
                'VCALENDAR must contain a VERSION property',
                self::ERR_MISSING_VERSION
            );
        }
    }
}
