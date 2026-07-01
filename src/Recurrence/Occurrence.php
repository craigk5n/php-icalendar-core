<?php

declare(strict_types=1);

namespace Icalendar\Recurrence;

use DateTimeImmutable;

/**
 * Immutable value object representing a single occurrence in a recurrence set.
 *
 * Bridging the gap between raw RRULE/RDATE/EXDATE properties and concrete
 * calendar instance dates.
 *
 * @immutable
 */
class Occurrence
{
    /**
     * @param DateTimeImmutable $start The start date/time of the occurrence
     * @param DateTimeImmutable|null $end The end date/time of the occurrence (null if not applicable)
     * @param bool $isRdate True if this occurrence originated from an RDATE property
     */
    public function __construct(
        private readonly DateTimeImmutable $start,
        private readonly ?DateTimeImmutable $end = null,
        private readonly bool $isRdate = false
    ) {
    }

    /**
     * Get the start date/time of the occurrence
     */
    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * Get the end date/time of the occurrence
     */
    public function getEnd(): ?DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * Check if this occurrence originated from an RDATE property
     */
    public function isRdate(): bool
    {
        return $this->isRdate;
    }
}
