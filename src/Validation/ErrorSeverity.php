<?php

declare(strict_types=1);

namespace Icalendar\Validation;

/**
 * Error severity levels for validation errors
 */
enum ErrorSeverity: string
{
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case FATAL = 'FATAL';
}
