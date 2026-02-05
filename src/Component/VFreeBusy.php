<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * VFREEBUSY component for free/busy time information
 *
 * The VFREEBUSY component defines busy time information for a calendar user.
 */
class VFreeBusy extends AbstractComponent
{
    public const ERR_MISSING_DTSTAMP = 'ICAL-VFB-001';
    public const ERR_MISSING_UID = 'ICAL-VFB-002';
    public const ERR_INVALID_PERIOD = 'ICAL-VFB-VAL-001';
    public const ERR_INVALID_FBTYPE = 'ICAL-VFB-VAL-002';

    public const FBTYPE_FREE = 'FREE';
    public const FBTYPE_BUSY = 'BUSY';
    public const FBTYPE_BUSY_UNAVAILABLE = 'BUSY-UNAVAILABLE';
    public const FBTYPE_BUSY_TENTATIVE = 'BUSY-TENTATIVE';

    /**
     * @var array<array{periods: string, fbtype: string}> Multiple FREEBUSY entries
     */
    private array $freebusyEntries = [];

    public function getName(): string
    {
        return 'VFREEBUSY';
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

    public function setDtEnd(string $dtEnd): self
    {
        $this->removeProperty('DTEND');
        $this->addProperty(GenericProperty::create('DTEND', $dtEnd));
        return $this;
    }

    public function getDtEnd(): ?string
    {
        $prop = $this->getProperty('DTEND');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setContact(string $contact): self
    {
        $this->removeProperty('CONTACT');
        $this->addProperty(GenericProperty::create('CONTACT', $contact));
        return $this;
    }

    public function getContact(): ?string
    {
        $prop = $this->getProperty('CONTACT');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setOrganizer(string $organizer): self
    {
        $this->removeProperty('ORGANIZER');
        $this->addProperty(GenericProperty::create('ORGANIZER', $organizer));
        return $this;
    }

    public function getOrganizer(): ?string
    {
        $prop = $this->getProperty('ORGANIZER');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setAttendee(string $attendee): self
    {
        $this->removeProperty('ATTENDEE');
        $this->addProperty(GenericProperty::create('ATTENDEE', $attendee));
        return $this;
    }

    public function getAttendee(): ?string
    {
        $prop = $this->getProperty('ATTENDEE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Add a FREEBUSY entry with periods and optional FBTYPE
     *
     * @param string $periods Comma-separated list of PERIOD values (e.g., "20240101T090000Z/20240101T100000Z")
     * @param string $fbtype FBTYPE parameter value (FREE, BUSY, BUSY-UNAVAILABLE, BUSY-TENTATIVE)
     * @return self
     * @throws ValidationException if FBTYPE is invalid
     */
    public function addFreeBusy(string $periods, string $fbtype = self::FBTYPE_BUSY): self
    {
        $validFbtypes = [
            self::FBTYPE_FREE,
            self::FBTYPE_BUSY,
            self::FBTYPE_BUSY_UNAVAILABLE,
            self::FBTYPE_BUSY_TENTATIVE,
        ];

        if (!in_array($fbtype, $validFbtypes, true)) {
            throw new ValidationException(
                "Invalid FBTYPE: {$fbtype}. Must be FREE, BUSY, BUSY-UNAVAILABLE, or BUSY-TENTATIVE",
                self::ERR_INVALID_FBTYPE
            );
        }

        // Basic validation of period format
        $periodList = explode(',', $periods);
        foreach ($periodList as $period) {
            $period = trim($period);
            if (!$this->isValidPeriod($period)) {
                throw new ValidationException(
                    "Invalid PERIOD value: {$period}. Must be start/end or start/duration format",
                    self::ERR_INVALID_PERIOD
                );
            }
        }

        $this->freebusyEntries[] = [
            'periods' => $periods,
            'fbtype' => $fbtype,
        ];

        return $this;
    }

    /**
     * Get all FREEBUSY entries
     *
     * @return array<array{periods: string, fbtype: string}>
     */
    public function getFreeBusyEntries(): array
    {
        return $this->freebusyEntries;
    }

    /**
     * Get all FREEBUSY entries of a specific type
     *
     * @param string $fbtype FBTYPE to filter by
     * @return array<array{periods: string, fbtype: string}>
     */
    public function getFreeBusyByType(string $fbtype): array
    {
        return array_filter(
            $this->freebusyEntries,
            fn($entry) => $entry['fbtype'] === $fbtype
        );
    }

    /**
     * Clear all FREEBUSY entries
     */
    public function clearFreeBusy(): self
    {
        $this->freebusyEntries = [];
        return $this;
    }

    /**
     * Validate a PERIOD value format
     *
     * PERIOD format is either:
     * - start/end (e.g., 20240101T090000Z/20240101T100000Z)
     * - start/duration (e.g., 20240101T090000Z/PT1H)
     */
    private function isValidPeriod(string $period): bool
    {
        if (strpos($period, '/') === false) {
            return false;
        }

        [$start, $endOrDuration] = explode('/', $period, 2);

        // Validate start is a DATE-TIME
        if (!preg_match('/^\d{8}T\d{6}Z?$/', $start)) {
            return false;
        }

        // Check if end is a DATE-TIME or DURATION
        if (preg_match('/^\d{8}T\d{6}Z?$/', $endOrDuration)) {
            // It's a DATE-TIME (end)
            return true;
        }

        if (preg_match('/^-?P/', $endOrDuration)) {
            // It's a DURATION
            return true;
        }

        return false;
    }

    public function validate(): void
    {
        if ($this->getProperty('DTSTAMP') === null) {
            throw new ValidationException(
                'VFREEBUSY must contain a DTSTAMP property',
                self::ERR_MISSING_DTSTAMP
            );
        }

        if ($this->getProperty('UID') === null) {
            throw new ValidationException(
                'VFREEBUSY must contain a UID property',
                self::ERR_MISSING_UID
            );
        }
    }
}
