<?php

declare(strict_types=1);

namespace Icalendar\Writer;

use Icalendar\Component\VCalendar;
use Icalendar\Exception\InvalidDataException;
use Icalendar\Exception\ValidationException;

/**
 * Main writer interface for iCalendar output
 */
interface WriterInterface
{
    /**
     * Write VCalendar to string
     *
     * Does not validate: whatever calendar is handed in is serialised as-is.
     * Use {@see writeValidated()} to gate on RFC 5545 conformance first.
     *
     * @throws InvalidDataException with error code ICAL-WRITE-XXX
     */
    public function write(VCalendar $calendar): string;

    /**
     * Validate the calendar, then write it
     *
     * Fail-fast: the component tree is validated (recursively) before
     * serialisation, and the first violation throws, so no output escapes for a
     * non-conformant calendar. For a valid calendar the result is identical to
     * {@see write()}.
     *
     * @throws ValidationException on the first RFC 5545 violation
     */
    public function writeValidated(VCalendar $calendar): string;

    /**
     * Write to file
     *
     * @throws \RuntimeException with error code ICAL-IO-001
     */
    public function writeToFile(VCalendar $calendar, string $filepath): void;

    /**
     * Configure line folding
     */
    public function setLineFolding(bool $fold, int $maxLength = 75): void;
}
