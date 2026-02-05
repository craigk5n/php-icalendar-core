<?php

declare(strict_types=1);

namespace Icalendar\Exception;

/**
 * Exception thrown during parsing operations
 */
class ParseException extends \Exception
{
    /** Parser error codes */
    public const ERR_INVALID_LINE_ENDING = 'ICAL-PARSE-001';
    public const ERR_MALFORMED_FOLDING = 'ICAL-PARSE-002';
    public const ERR_LINE_TOO_LONG = 'ICAL-PARSE-003';
    public const ERR_UTF8_SEQUENCE_BROKEN = 'ICAL-PARSE-004';
    public const ERR_BINARY_CORRUPTION = 'ICAL-PARSE-005';
    public const ERR_INVALID_PROPERTY_FORMAT = 'ICAL-PARSE-006';
    public const ERR_INVALID_PROPERTY_NAME = 'ICAL-PARSE-007';
    public const ERR_INVALID_PARAMETER_FORMAT = 'ICAL-PARSE-008';
    public const ERR_UNCLOSED_QUOTED_STRING = 'ICAL-PARSE-009';
    public const ERR_INVALID_RFC6868_ENCODING = 'ICAL-PARSE-010';
    public const ERR_INVALID_MULTI_VALUE_PARAM = 'ICAL-PARSE-011';
    public const ERR_TYPE_DECLARATION_MISMATCH = 'ICAL-PARSE-012';

    /** Data type error codes */
    public const ERR_INVALID_BINARY = 'ICAL-TYPE-001';
    public const ERR_INVALID_BOOLEAN = 'ICAL-TYPE-002';
    public const ERR_INVALID_CAL_ADDRESS = 'ICAL-TYPE-003';
    public const ERR_INVALID_DATE = 'ICAL-TYPE-004';
    public const ERR_INVALID_DATE_TIME = 'ICAL-TYPE-005';
    public const ERR_INVALID_DURATION = 'ICAL-TYPE-006';
    public const ERR_INVALID_FLOAT = 'ICAL-TYPE-007';
    public const ERR_INVALID_INTEGER = 'ICAL-TYPE-008';
    public const ERR_INVALID_PERIOD = 'ICAL-TYPE-009';
    public const ERR_INVALID_RECUR = 'ICAL-TYPE-010';
    public const ERR_INVALID_TEXT = 'ICAL-TYPE-011';
    public const ERR_INVALID_TIME = 'ICAL-TYPE-012';
    public const ERR_INVALID_URI = 'ICAL-TYPE-013';
    public const ERR_INVALID_UTC_OFFSET = 'ICAL-TYPE-014';

    /** IO error codes */
    public const ERR_FILE_NOT_FOUND = 'ICAL-IO-001';
    public const ERR_FILE_WRITE = 'ICAL-IO-002';
    public const ERR_PERMISSION_DENIED = 'ICAL-IO-003';

    public function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly int $contentLineNumber = 0,
        private readonly ?string $contentLine = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContentLineNumber(): int
    {
        return $this->contentLineNumber;
    }

    public function getContentLine(): ?string
    {
        return $this->contentLine;
    }
}
