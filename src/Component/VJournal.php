<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * VJOURNAL component for journal entries
 *
 * The VJOURNAL component defines a journal entry on a calendar.
 */
class VJournal extends AbstractComponent
{
    public const ERR_MISSING_DTSTAMP = 'ICAL-VJOURNAL-001';
    public const ERR_MISSING_UID = 'ICAL-VJOURNAL-002';
    public const ERR_INVALID_STATUS = 'ICAL-VJOURNAL-VAL-001';
    public const ERR_INVALID_CLASS = 'ICAL-VJOURNAL-VAL-002';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_FINAL = 'FINAL';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const CLASS_PUBLIC = 'PUBLIC';
    public const CLASS_PRIVATE = 'PRIVATE';
    public const CLASS_CONFIDENTIAL = 'CONFIDENTIAL';

    public function getName(): string
    {
        return 'VJOURNAL';
    }

    public function setDtStamp(string $dtStamp): self
    {
        $this->removeProperty('DTSTAMP');
        $this->addProperty(GenericProperty::create('DTSTAMP', $dtStamp));
        return $this;
    }

    public function getDtStamp(): ?string
    {
        $prop = $this->getProperty('DTSTAMP');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setUid(string $uid): self
    {
        $this->removeProperty('UID');
        $this->addProperty(GenericProperty::create('UID', $uid));
        return $this;
    }

    public function getUid(): ?string
    {
        $prop = $this->getProperty('UID');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setDtStart(string $dtStart): self
    {
        $this->removeProperty('DTSTART');
        $this->addProperty(GenericProperty::create('DTSTART', $dtStart));
        return $this;
    }

    public function getDtStart(): ?string
    {
        $prop = $this->getProperty('DTSTART');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setSummary(string $summary): self
    {
        $this->removeProperty('SUMMARY');
        $this->addProperty(GenericProperty::create('SUMMARY', $summary));
        return $this;
    }

    public function getSummary(): ?string
    {
        $prop = $this->getProperty('SUMMARY');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Add a description to the journal entry
     *
     * VJOURNAL can have multiple DESCRIPTION properties unlike VEVENT/VTODO
     */
    public function addDescription(string $description): self
    {
        $this->addProperty(GenericProperty::create('DESCRIPTION', $description));
        return $this;
    }

    /**
     * Set a single description, replacing any existing descriptions
     */
    public function setDescription(string $description): self
    {
        $this->removeProperty('DESCRIPTION');
        $this->addProperty(GenericProperty::create('DESCRIPTION', $description));
        return $this;
    }

    /**
     * Get all descriptions
     *
     * @return string[]
     */
    public function getDescriptions(): array
    {
        return array_map(
            fn($prop) => $prop->getValue()->getRawValue(),
            $this->getAllProperties('DESCRIPTION')
        );
    }

    /**
     * Get the first description (for compatibility)
     */
    public function getDescription(): ?string
    {
        $descriptions = $this->getAllProperties('DESCRIPTION');
        if (empty($descriptions)) {
            return null;
        }
        return $descriptions[0]->getValue()->getRawValue();
    }

    public function setCategories(string ...$categories): self
    {
        $this->removeProperty('CATEGORIES');
        $categoriesValue = implode(',', $categories);
        $this->addProperty(GenericProperty::create('CATEGORIES', $categoriesValue));
        return $this;
    }

    public function getCategories(): array
    {
        $prop = $this->getProperty('CATEGORIES');
        if ($prop === null) {
            return [];
        }
        $value = $prop->getValue()->getRawValue();
        if ($value === '') {
            return [];
        }
        return explode(',', $value);
    }

    public function setClass(string $class): self
    {
        $validClasses = [
            self::CLASS_PUBLIC,
            self::CLASS_PRIVATE,
            self::CLASS_CONFIDENTIAL,
        ];
        if (!in_array($class, $validClasses, true)) {
            throw new ValidationException(
                "Invalid CLASS: {$class}. Must be PUBLIC, PRIVATE, or CONFIDENTIAL",
                self::ERR_INVALID_CLASS
            );
        }
        $this->removeProperty('CLASS');
        $this->addProperty(GenericProperty::create('CLASS', $class));
        return $this;
    }

    public function getClass(): ?string
    {
        $prop = $this->getProperty('CLASS');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setStatus(string $status): self
    {
        $validStatuses = [
            self::STATUS_DRAFT,
            self::STATUS_FINAL,
            self::STATUS_CANCELLED,
        ];
        if (!in_array($status, $validStatuses, true)) {
            throw new ValidationException(
                "Invalid VJOURNAL status: {$status}. Must be DRAFT, FINAL, or CANCELLED",
                self::ERR_INVALID_STATUS
            );
        }
        $this->removeProperty('STATUS');
        $this->addProperty(GenericProperty::create('STATUS', $status));
        return $this;
    }

    public function getStatus(): ?string
    {
        $prop = $this->getProperty('STATUS');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setRrule(string $rrule): self
    {
        $this->removeProperty('RRULE');
        $this->addProperty(GenericProperty::create('RRULE', $rrule));
        return $this;
    }

    public function getRrule(): ?string
    {
        $prop = $this->getProperty('RRULE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function validate(): void
    {
        if ($this->getProperty('DTSTAMP') === null) {
            throw new ValidationException(
                'VJOURNAL must contain a DTSTAMP property',
                self::ERR_MISSING_DTSTAMP
            );
        }

        if ($this->getProperty('UID') === null) {
            throw new ValidationException(
                'VJOURNAL must contain a UID property',
                self::ERR_MISSING_UID
            );
        }
    }
}
