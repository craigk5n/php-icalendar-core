<?php

declare(strict_types=1);

namespace Icalendar\Writer;

use Icalendar\Parser\ContentLine;
use Icalendar\Property\PropertyInterface;
use Icalendar\Writer\ValueWriter\ValueWriterFactory;

/**
 * Serializes properties with parameters and values
 *
 * Handles property serialization according to RFC 5545:
 * - Serializes property name
 * - Serializes parameters with proper quoting
 * - Applies RFC 6868 encoding for parameter values
 * - Serializes values using appropriate value writer
 * - Handles multi-valued parameters
 * - Handles properties without parameters
 */
class PropertyWriter
{
    private ValueWriterFactory $valueWriterFactory;

    public function __construct(?ValueWriterFactory $valueWriterFactory = null)
    {
        $this->valueWriterFactory = $valueWriterFactory ?? new ValueWriterFactory();
    }

    /**
     * Write a property to iCalendar format
     *
     * @param PropertyInterface|ContentLine|array{name: string, parameters: array<string, string>, value: string, type?: string} $property
     * @return string The serialized property line (unfolded)
     */
    public function write(PropertyInterface|ContentLine|array $property): string
    {
        if ($property instanceof PropertyInterface) {
            return $this->writePropertyInterface($property);
        }

        if ($property instanceof ContentLine) {
            return $this->writeContentLine($property);
        }

        return $this->writeArray($property);
    }

    /**
     * Write a PropertyInterface to iCalendar format
     */
    private function writePropertyInterface(PropertyInterface $property): string
    {
        $name = $property->getName();
        $parameters = $property->getParameters();
        $value = $property->getValue();

        // Serialize the value using its type
        $valueType = $value->getType();
        $valueString = $this->valueWriterFactory->write($value->getRawValue(), $valueType);

        return $this->buildPropertyLine($name, $parameters, $valueString);
    }

    /**
     * Write a ContentLine to iCalendar format
     */
    private function writeContentLine(ContentLine $contentLine): string
    {
        $name = $contentLine->getName();
        $parameters = $contentLine->getParameters();
        $value = $contentLine->getValue();

        return $this->buildPropertyLine($name, $parameters, $value);
    }

    /**
     * Write a property from an array
     *
     * @param array{name: string, parameters: array<string, string>, value: string, type?: string} $property
     */
    private function writeArray(array $property): string
    {
        $name = $property['name'];
        $parameters = $property['parameters'];
        $value = $property['value'];
        $type = $property['type'] ?? null;

        // If type is specified, use the value writer factory
        if ($type !== null) {
            $value = $this->valueWriterFactory->write($value, $type);
        }

        return $this->buildPropertyLine($name, $parameters, $value);
    }

    /**
     * Build a property line from components
     *
     * @param string $name The property name
     * @param array<string, string> $parameters The parameters
     * @param string $value The serialized value
     * @return string The complete property line
     */
    private function buildPropertyLine(string $name, array $parameters, string $value): string
    {
        $line = $name;

        // Add parameters
        foreach ($parameters as $paramName => $paramValue) {
            $line .= ';' . $this->writeParameter($paramName, $paramValue);
        }

        // Add the value
        $line .= ':' . $value;

        return $line;
    }

    /**
     * Write a single parameter
     *
     * @param string $name The parameter name
     * @param string $value The parameter value
     * @return string The serialized parameter
     */
    private function writeParameter(string $name, string $value): string
    {
        // Check if value needs quoting (contains : ; ,)
        if ($this->needsQuoting($value)) {
            $value = $this->quoteParameterValue($value);
        }

        return $name . '=' . $value;
    }

    /**
     * Check if a parameter value needs quoting
     *
     * Per RFC 5545 §3.2 and PRD §3.4.2, parameter values containing
     * COLON (:), SEMICOLON (;), or COMMA (,) must be quoted.
     *
     * Additionally, per RFC 6868, values containing DQUOTE ("), NEWLINE,
     * CIRCUMFLEX (^), or SPACE must be quoted so they can be properly encoded
     * or handled.
     *
     * This implementation follows common practice which is more lenient
     * than strict RFC 5545 iana-token requirements.
     */
    private function needsQuoting(string $value): bool
    {
        // Empty values don't need quoting
        if ($value === '') {
            return false;
        }

        // Check for characters that require quoting: : ; , " space \n \r ^
        return strpbrk($value, ":;,\" \n\r\x00\x09^") !== false;
    }

    /**
     * Quote a parameter value and apply RFC 6868 encoding
     *
     * RFC 6868 encoding:
     * - " becomes ^'
     * - newline (\n) becomes ^n
     * - ^ becomes ^^
     */
    private function quoteParameterValue(string $value): string
    {
        // Apply RFC 6868 encoding
        $encoded = $this->encodeRfc6868($value);

        // Wrap in quotes
        return '"' . $encoded . '"';
    }

    /**
     * Encode parameter value according to RFC 6868
     *
     * ^n -> newline (LF, CR, or CRLF)
     * ^^ -> caret (^)
     * ^' -> double quote (")
     *
     * @param string $value The value to encode
     * @return string The encoded value
     */
    private function encodeRfc6868(string $value): string
    {
        // Normalize all newline sequences to a single representation first
        // Replace CRLF with LF, then CR with LF
        $value = str_replace("\r\n", "\n", $value);
        $value = str_replace("\r", "\n", $value);

        // Must escape ^ FIRST (before other replacements add more ^)
        $value = str_replace('^', '^^', $value);

        // Then encode other special characters
        $value = str_replace("\n", '^n', $value);
        $value = str_replace('"', "^'", $value);

        return $value;
    }

    /**
     * Get the value writer factory
     */
    public function getValueWriterFactory(): ValueWriterFactory
    {
        return $this->valueWriterFactory;
    }

    /**
     * Set the value writer factory
     */
    public function setValueWriterFactory(ValueWriterFactory $factory): void
    {
        $this->valueWriterFactory = $factory;
    }
}
