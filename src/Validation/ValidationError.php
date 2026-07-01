<?php

declare(strict_types=1);

namespace Icalendar\Validation;

/**
 * Represents a single validation error
 */
class ValidationError
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly string $component,
        public readonly ?string $property,
        public readonly ?string $line,
        public readonly int $lineNumber,
        public readonly ErrorSeverity $severity
    ) {
    }

    /**
     * Convert to array for serialization
     * 
     * @return array{code: string, message: string, component: string, property: string|null, line: string|null, lineNumber: int, severity: string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'component' => $this->component,
            'property' => $this->property,
            'line' => $this->line,
            'lineNumber' => $this->lineNumber,
            'severity' => $this->severity->value,
        ];
    }

    /**
     * Create from array (for deserialization)
     * 
     * @param array{code: string, message: string, component: string, property?: string|null, line?: string|null, lineNumber: int, severity: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['code'],
            $data['message'],
            $data['component'],
            $data['property'] ?? null,
            $data['line'] ?? null,
            $data['lineNumber'],
            ErrorSeverity::from($data['severity'])
        );
    }
}
