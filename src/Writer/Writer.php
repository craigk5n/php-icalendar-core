<?php

declare(strict_types=1);

namespace Icalendar\Writer;

use Icalendar\Component\VCalendar;
use Icalendar\Exception\InvalidDataException;

/**
 * Main writer implementation
 */
class Writer implements WriterInterface
{
    private bool $foldLines = true;
    private int $maxLineLength = 75;

    public function write(VCalendar $calendar): string
    {
        // TODO: Implement actual writing
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//PHP//iCalendar//EN\r\nEND:VCALENDAR\r\n";
    }

    public function writeToFile(VCalendar $calendar, string $filepath): void
    {
        $content = $this->write($calendar);
        $result = file_put_contents($filepath, $content, LOCK_EX);

        if ($result === false) {
            throw new \RuntimeException(
                "Failed to write to file: {$filepath}",
                0
            );
        }
    }

    public function setLineFolding(bool $fold, int $maxLength = 75): void
    {
        $this->foldLines = $fold;
        $this->maxLineLength = $maxLength;
    }
}
