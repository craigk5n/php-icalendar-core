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
     */
    public function __construct(
        private readonly string $rawLine,
        private readonly string $name,
        private readonly array $parameters,
        private readonly string $value
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
     * @throws ParseException if the line format is invalid
     */
    public static function parse(string $line): self
    {
        // Validate line contains a colon (required by RFC 5545)
        if (!str_contains($line, ':')) {
            throw new ParseException(
                'Invalid content line format: missing colon separator',
                ParseException::ERR_INVALID_PROPERTY_FORMAT,
                0,
                $line
            );
        }

        // Find the first colon to separate name/params from value
        $colonPos = strpos($line, ':');
        $nameAndParams = substr($line, 0, $colonPos);
        $value = substr($line, $colonPos + 1);

        // Parse name and parameters
        $parts = explode(';', $nameAndParams);
        $name = array_shift($parts);

        // Validate property name (must not be empty)
        if (empty($name)) {
            throw new ParseException(
                'Invalid content line format: empty property name',
                ParseException::ERR_INVALID_PROPERTY_NAME,
                0,
                $line
            );
        }

        // Parse parameters
        $parameters = [];
        foreach ($parts as $param) {
            if (empty($param)) {
                continue; // Skip empty parameters
            }

            $eqPos = strpos($param, '=');
            if ($eqPos === false) {
                // Parameter without value (treat as empty string per RFC)
                $parameters[$param] = '';
            } else {
                $paramName = substr($param, 0, $eqPos);
                $paramValue = substr($param, $eqPos + 1);
                $parameters[$paramName] = $paramValue;
            }
        }

        return new self($line, $name, $parameters, $value);
    }
}
