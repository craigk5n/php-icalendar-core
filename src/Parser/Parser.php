<?php

declare(strict_types=1);

namespace Icalendar\Parser;

use Icalendar\Component\VCalendar;
use Icalendar\Exception\ParseException;
use Icalendar\Validation\ValidationError;

/**
 * Main parser implementation
 */
class Parser implements ParserInterface
{
    private bool $strict = false;

    /** @var ValidationError[] */
    private array $errors = [];

    public function parse(string $data): VCalendar
    {
        // TODO: Implement actual parsing
        return new VCalendar();
    }

    public function parseFile(string $filepath): VCalendar
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            throw new ParseException(
                "File not found or unreadable: {$filepath}",
                ParseException::ERR_FILE_NOT_FOUND
            );
        }

        $data = file_get_contents($filepath);
        if ($data === false) {
            throw new ParseException(
                "Failed to read file: {$filepath}",
                ParseException::ERR_FILE_NOT_FOUND
            );
        }

        return $this->parse($data);
    }

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
