<?php

declare(strict_types=1);

namespace Icalendar\Validation;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use Traversable;

/** @implements IteratorAggregate<int, ValidationError> */
final readonly class ValidationResult implements IteratorAggregate, Countable
{
    /** @var list<ValidationError> */
    private array $errors;

    public function __construct(ValidationError ...$errors)
    {
        $this->errors = array_values($errors);
    }

    public static function empty(): self
    {
        return new self();
    }

    public static function fromErrors(ValidationError ...$errors): self
    {
        return new self(...$errors);
    }

    /** @param ValidationError[] $errors */
    public static function fromArray(array $errors): self
    {
        return new self(...$errors);
    }

    /** @return list<ValidationError> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->errors);
    }

    #[\Override]
    public function count(): int
    {
        return count($this->errors);
    }

    public function isEmpty(): bool
    {
        return empty($this->errors);
    }

    public function hasErrors(): bool
    {
        return !$this->isEmpty();
    }

    public function hasErrorSeverity(ErrorSeverity $severity): bool
    {
        foreach ($this->errors as $error) {
            if ($error->severity === $severity) {
                return true;
            }
        }
        return false;
    }

    /** @return list<ValidationError> */
    public function getErrorsBySeverity(ErrorSeverity $severity): array
    {
        return array_values(array_filter(
            $this->errors,
            fn(ValidationError $e) => $e->severity === $severity
        ));
    }

    /** @return list<string> */
    public function getErrorCodes(): array
    {
        return array_map(fn(ValidationError $e) => $e->code, $this->errors);
    }

    public function merge(ValidationResult $other): self
    {
        return new self(...$this->errors, ...$other->errors);
    }

    /** @return array{WARNING: int, ERROR: int, FATAL: int} */
    public function getErrorCounts(): array
    {
        $counts = [
            ErrorSeverity::WARNING->value => 0,
            ErrorSeverity::ERROR->value => 0,
            ErrorSeverity::FATAL->value => 0,
        ];

        foreach ($this->errors as $error) {
            $counts[$error->severity->value]++;
        }

        return $counts;
    }

    public function firstError(): ?ValidationError
    {
        return $this->errors[0] ?? null;
    }

    public function firstErrorByCode(string $code): ?ValidationError
    {
        foreach ($this->errors as $error) {
            if ($error->code === $code) {
                return $error;
            }
        }
        return null;
    }
}
