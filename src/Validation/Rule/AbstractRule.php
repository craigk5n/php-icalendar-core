<?php

declare(strict_types=1);

namespace Icalendar\Validation\Rule;

use Icalendar\Component\ComponentInterface;
use Icalendar\Validation\ValidationResult;

abstract class AbstractRule implements RuleInterface
{
    protected function createError(
        string $code,
        string $message,
        string $component,
        ?string $property = null,
        ?string $line = null,
        int $lineNumber = 0
    ): ValidationResult {
        return ValidationResult::fromErrors(
            new \Icalendar\Validation\ValidationError(
                $code,
                $message,
                $component,
                $property,
                $line,
                $lineNumber,
                \Icalendar\Validation\ErrorSeverity::ERROR
            )
        );
    }

    protected function createWarning(
        string $code,
        string $message,
        string $component,
        ?string $property = null,
        ?string $line = null,
        int $lineNumber = 0
    ): ValidationResult {
        return ValidationResult::fromErrors(
            new \Icalendar\Validation\ValidationError(
                $code,
                $message,
                $component,
                $property,
                $line,
                $lineNumber,
                \Icalendar\Validation\ErrorSeverity::WARNING
            )
        );
    }

    #[\Override]
    public function validate(ComponentInterface $component): ValidationResult
    {
        return ValidationResult::empty();
    }

    #[\Override]
    public function appliesTo(string $componentName): bool
    {
        return true;
    }
}
