<?php

declare(strict_types=1);

namespace Icalendar\Parser;

use Icalendar\Component\VCalendar;
use Icalendar\Exception\ParseException;
use Icalendar\Validation\ValidationError;

/**
 * Main parser interface for iCalendar data
 */
interface ParserInterface
{
    /**
     * Parse iCalendar data string
     *
     * @throws ParseException with error code ICAL-PARSE-XXX
     */
    public function parse(string $data): VCalendar;

    /**
     * Parse from file
     *
     * @throws ParseException with error code ICAL-PARSE-XXX or ICAL-IO-XXX
     */
    public function parseFile(string $filepath): VCalendar;

    /**
     * Set strict mode (throw on unknown props/params)
     */
    public function setStrict(bool $strict): void;

    /**
     * Get last parse errors (non-fatal)
     *
     * @return ValidationError[]
     */
    public function getErrors(): array;
}
