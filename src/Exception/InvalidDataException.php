<?php

declare(strict_types=1);

namespace Icalendar\Exception;

/**
 * Exception thrown when data is invalid
 */
class InvalidDataException extends \Exception
{
    /** Writer error codes */
    public const ERR_WRITE_LINE_ENDING = 'ICAL-WRITE-001';
    public const ERR_WRITE_FOLDING = 'ICAL-WRITE-002';
    public const ERR_WRITE_UTF8_SPLIT = 'ICAL-WRITE-003';
    public const ERR_WRITE_BOUNDARY = 'ICAL-WRITE-004';
    public const ERR_WRITE_SERIALIZATION = 'ICAL-WRITE-005';
    public const ERR_WRITE_TEXT_ESCAPE = 'ICAL-WRITE-006';
    public const ERR_WRITE_PARAM_QUOTE = 'ICAL-WRITE-007';
    public const ERR_WRITE_RFC6868 = 'ICAL-WRITE-008';
    public const ERR_WRITE_BINARY_WRAP = 'ICAL-WRITE-009';
    public const ERR_WRITE_DATETIME = 'ICAL-WRITE-010';
    public const ERR_WRITE_MISSING_PRODID = 'ICAL-WRITE-011';
    public const ERR_WRITE_MISSING_VERSION = 'ICAL-WRITE-012';
    public const ERR_WRITE_MISSING_REQUIRED = 'ICAL-WRITE-013';

    /** Security error codes */
    public const ERR_MAX_DEPTH_EXCEEDED = 'ICAL-SEC-001';
    public const ERR_XXE_DETECTED = 'ICAL-SEC-002';
    public const ERR_SSRF_ATTEMPT = 'ICAL-SEC-003';

    public function __construct(
        string $message,
        private readonly string $errorCode,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
