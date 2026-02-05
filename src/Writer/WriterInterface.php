<?php

declare(strict_types=1);

namespace Icalendar\Writer;

use Icalendar\Component\VCalendar;
use Icalendar\Exception\InvalidDataException;

/**
 * Main writer interface for iCalendar output
 */
interface WriterInterface
{
    /**
     * Write VCalendar to string
     *
     * @throws InvalidDataException with error code ICAL-WRITE-XXX
     */
    public function write(VCalendar $calendar): string;

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
