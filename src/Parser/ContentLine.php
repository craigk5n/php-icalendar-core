<?php

declare(strict_types=1);

namespace Icalendar\Parser;

use Icalendar\Exception\ParseException;

/**
 * Represents a single parsed content line from an iCalendar document
 *
 * Content lines in iCalendar follow the format:
 * name *(";" param) ":" value
 *
 * Example: SUMMARY;LANGUAGE=en:Meeting
 */
class ContentLine
{
    /**
     * @param string $rawLine The raw content line as it appeared in the source
     * @param string $name The property name (e.g., SUMMARY, DTSTART)
     * @param array<string, string> $parameters Associative array of parameter name => value
     * @param string $value The property value
     * @param int $lineNumber The line number in the source file
     */
    public function __construct(
        private readonly string $rawLine,
        private readonly string $name,
        private readonly array $parameters,
        private readonly string $value,
        private readonly int $lineNumber = 0
    ) {
    }

    /**
     * Get the raw content line as it appeared in the source
     */
    public function getRawLine(): string
    {
        return $this->rawLine;
    }

    /**
     * Get the property name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get all parameters as an associative array
     *
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get a specific parameter value
     */
    public function getParameter(string $name): ?string
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Check if a parameter exists
     */
    public function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Get the property value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Check if this content line has any parameters
     */
    public function hasParameters(): bool
    {
        return count($this->parameters) > 0;
    }

    /**
     * Get the line number
     */
    public function getContentLineNumber(): int
    {
        return $this->lineNumber;
    }

    /**
     * Convert to string representation for debugging
     */
    public function __toString(): string
    {
        $params = '';
        if ($this->hasParameters()) {
            $paramParts = [];
            foreach ($this->parameters as $name => $value) {
                $paramParts[] = "{$name}={$value}";
            }
            $params = ';' . implode(';', $paramParts);
        }

        return "{$this->name}{$params}:{$this->value}";
    }

    /**
     * Parse a content line string into a ContentLine object
     *
     * This method delegates to PropertyParser which handles:
     * - Quoted parameter values (for values containing :, ;, ,)
     * - RFC 6868 parameter value encoding (^n, ^^, ^')
     * - Multi-valued comma-separated parameters
     * - Proper X-name validation
     *
     * @throws ParseException if the line format is invalid
     */
    public static function parse(string $line, int $lineNumber = 0): self
    {
        $parser = new PropertyParser();
        return $parser->parse($line, $lineNumber);
    }
}
