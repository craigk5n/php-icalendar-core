<?php

declare(strict_types=1);

namespace Icalendar\Parser;

use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VTodo;
use Icalendar\Component\VJournal;
use Icalendar\Component\VFreeBusy;
use Icalendar\Component\VTimezone;
use Icalendar\Component\Standard;
use Icalendar\Component\Daylight;
use Icalendar\Component\VAlarm;
use Icalendar\Component\VAvailability;
use Icalendar\Component\Available;
use Icalendar\Component\Participant;
use Icalendar\Component\GenericComponent;
use Icalendar\Exception\ParseException;
use Icalendar\Property\GenericProperty;
use Icalendar\Value\TextValue;
use Icalendar\Exception\ValidationException;
use Icalendar\Validation\SecurityValidator;
use Icalendar\Validation\ValidationError;
use Icalendar\Validation\ErrorSeverity;
use Icalendar\Parser\ValueParser\ValueParserFactory;

/**
 * Main parser implementation
 *
 * Parses iCalendar data into component structures.
 */
class Parser implements ParserInterface
{
    public const STRICT = true;
    public const LENIENT = false;

    private bool $mode; // true for strict, false for lenient

    /** @var ValidationError[] */
    private array $errors = [];

    private SecurityValidator $securityValidator;

    private int $currentDepth = 0;

    private PropertyParser $propertyParser;

    private ValueParserFactory $valueParserFactory;

    /**
     * @param bool $mode The parsing mode: Parser::STRICT or Parser::LENIENT. Defaults to Parser::STRICT.
     */
    public function __construct(bool $mode = self::STRICT)
    {
        $this->mode = $mode;
        $this->securityValidator = new SecurityValidator();
        $this->propertyParser = new PropertyParser();
        $this->valueParserFactory = new ValueParserFactory();
        $this->valueParserFactory->setStrict($mode); // Pass the mode to the value parser factory
    }

    #[\Override]
    public function parse(string $data): VCalendar
    {
        $this->errors = [];
        $this->currentDepth = 0;

        $lexer = new Lexer();
        $contentLines = [];

        foreach ($lexer->tokenize($data) as $line) {
            $contentLines[] = $line;
        }

        return $this->buildCalendar($contentLines);
    }

    /**
     * Build a VCalendar from content lines
     *
     * @param ContentLine[] $contentLines
     * @return VCalendar
     */
    private function buildCalendar(array $contentLines): VCalendar
    {
        $calendar = new VCalendar();
        $componentStack = [$calendar];
        
        // Stack for property buffers, one for each component in the component stack
        $propertyBuffers = [[]];

        foreach ($contentLines as $contentLine) {
            // Re-parse with strict flag if needed (lexer only does basic parsing)
            if ($this->mode === self::STRICT) {
                $contentLine = $this->propertyParser->parse($contentLine->getRawLine(), $contentLine->getContentLineNumber(), true);
            }

            $name = $contentLine->getName();
            /** @var ComponentInterface $currentComponent */
            $currentComponent = end($componentStack);

            // Handle BEGIN marker
            if ($name === 'BEGIN') {
                $componentName = $contentLine->getValue();

                // BEGIN:VCALENDAR uses the existing calendar object
                if (strtoupper($componentName) === 'VCALENDAR') {
                    if ($this->currentDepth > 0) {
                        // Nested VCALENDAR - create a new one
                        $nestedCalendar = new VCalendar();
                        $componentStack[] = $nestedCalendar;
                        $propertyBuffers[] = [];
                    }
                    $this->currentDepth++;
                    $this->securityValidator->checkDepth($this->currentDepth);
                    continue;
                }

                $component = $this->createComponent($componentName);

                if ($component instanceof GenericComponent) {
                    if ($this->mode === self::STRICT) {
                        throw new ParseException(
                            "Unknown component type: {$componentName}",
                            ParseException::ERR_INVALID_PROPERTY_NAME,
                            $contentLine->getContentLineNumber(),
                            $contentLine->getRawLine()
                        );
                    }

                    $this->addError(
                        'ICAL-PARSE-007',
                        "Unknown component type: {$componentName}",
                        'UNKNOWN',
                        $name,
                        $contentLine->getRawLine(),
                        $contentLine->getContentLineNumber(),
                        ErrorSeverity::WARNING
                    );
                }

                $this->currentDepth++;
                $this->securityValidator->checkDepth($this->currentDepth);

                $componentStack[] = $component;
                $propertyBuffers[] = []; // New buffer for this component
                continue;
            }

            // Handle END marker
            if ($name === 'END') {
                $componentName = strtoupper($contentLine->getValue());

                if ($componentName === 'VCALENDAR' && count($componentStack) === 1) {
                    // Top-level VCALENDAR closing
                    $props = array_pop($propertyBuffers);
                    $finalProperties = $this->resolvePropertyConflicts($props ?? []);
                    foreach ($finalProperties as $prop) {
                        $calendar->addProperty($prop);
                    }
                    $this->currentDepth--;
                    continue;
                }

                if (count($componentStack) > 1) {
                    $completedComponent = array_pop($componentStack);
                    // $completedComponent is never null here due to the check count($componentStack) > 1
                    
                    $completedProperties = array_pop($propertyBuffers);
                    $parentComponent = end($componentStack);
                    if ($parentComponent === false) {
                        throw new \LogicException('Parent component not found');
                    }

                    if (strtoupper($completedComponent->getName()) !== $componentName) {
                        $this->addError(
                            'ICAL-PARSE-006',
                            "END marker mismatch: expected {$completedComponent->getName()}, got {$componentName}",
                            $completedComponent->getName(),
                            $name,
                            $contentLine->getRawLine(),
                            $contentLine->getContentLineNumber(),
                            ErrorSeverity::ERROR
                        );
                    }

                    // Resolve property conflicts for the completed component
                    $finalProperties = $this->resolvePropertyConflicts($completedProperties ?? []);
                    foreach ($finalProperties as $prop) {
                        $completedComponent->addProperty($prop);
                    }

                    $parentComponent->addComponent($completedComponent);
                    $this->currentDepth--;
                } else {
                    $this->addError(
                        'ICAL-PARSE-006',
                        'END marker without matching BEGIN',
                        'UNKNOWN',
                        $name,
                        $contentLine->getRawLine(),
                        $contentLine->getContentLineNumber(),
                        ErrorSeverity::ERROR
                    );
                }
                continue;
            }

            // Handle properties
            $value = $contentLine->getValue();
            $parameters = $contentLine->getParameters();

            // Skip properties not allowed at the top level of VCALENDAR, unless they are X- properties
            if ($currentComponent === $calendar
                && $name !== 'VERSION' && $name !== 'PRODID'
                && $name !== 'CALSCALE' && $name !== 'METHOD'
                && $name !== 'REFRESH-INTERVAL' && $name !== 'COLOR'
                && !str_starts_with($name, 'X-')) {
                continue;
            }

            // Temporarily store property in the current component's buffer
            try {
                $parser = $this->valueParserFactory->getParserForProperty($name, $parameters);
                $parsedValue = $parser->parse($value, $parameters);
                $type = $parser->getType();
                $propertyValue = $this->formatParsedValue($parsedValue, $type);
                $property = new GenericProperty($name, new \Icalendar\Value\GenericValue($propertyValue, $type), $parameters);
                
                // Add to the buffer for the current component
                $propertyBuffers[count($propertyBuffers) - 1][] = $property;
            } catch (\Exception $e) {
                // Add error and potentially throw if in strict mode
                $this->addError(
                    'ICAL-PARSE-006', // Generic parse error code
                    $e->getMessage(),
                    $currentComponent->getName(), // Component name
                    $name, // Property name
                    $contentLine->getRawLine(),
                    $contentLine->getContentLineNumber(),
                    $this->mode === self::STRICT ? ErrorSeverity::ERROR : ErrorSeverity::WARNING
                );

                if ($this->mode === self::STRICT) {
                    if ($e instanceof ParseException) {
                        throw $e;
                    }
                    throw new ParseException($e->getMessage(), 'ICAL-PARSE-006', $contentLine->getContentLineNumber(), $contentLine->getRawLine(), $e);
                }
            }
        }

        // Final cleanup: if VCALENDAR was not explicitly closed or properties remain
        if (!empty($propertyBuffers)) {
            $remainingProperties = array_pop($propertyBuffers);
            if (!empty($remainingProperties)) {
                $finalProperties = $this->resolvePropertyConflicts($remainingProperties);
                foreach ($finalProperties as $prop) {
                    $calendar->addProperty($prop);
                }
            }
        }

        return $calendar;
    }

    /**
     * Resolve conflicts between DESCRIPTION and STYLED-DESCRIPTION properties
     * according to RFC 9073.
     *
     * @param GenericProperty[] $properties The list of properties for a component.
     * @return GenericProperty[] The filtered list of properties.
     */
    private function resolvePropertyConflicts(array $properties): array
    {
        $styledDescPresent = false;
        foreach ($properties as $property) {
            if (strtoupper($property->getName()) === 'STYLED-DESCRIPTION') {
                $styledDescPresent = true;
                break;
            }
        }

        if (!$styledDescPresent) {
            return $properties;
        }

        $descriptionIndices = [];
        $finalProperties = [];

        foreach ($properties as $property) {
            $name = strtoupper($property->getName());
            if ($name === 'STYLED-DESCRIPTION') {
                $finalProperties[] = $property;
            } elseif ($name === 'DESCRIPTION') {
                $descriptionIndices[] = [
                    'index' => count($finalProperties),
                    'property' => $property
                ];
                $finalProperties[] = $property;
            } else {
                $finalProperties[] = $property;
            }
        }

        if (!empty($descriptionIndices)) {
            $toRemove = [];
            foreach ($descriptionIndices as $info) {
                $params = $info['property']->getParameters();
                $derivedParam = $params['DERIVED'] ?? null;
                if ($derivedParam === null || strtoupper($derivedParam) !== 'TRUE') {
                    $toRemove[] = $info['index'];
                }
            }

            if (!empty($toRemove)) {
                foreach (array_reverse($toRemove) as $index) {
                    array_splice($finalProperties, $index, 1);
                }
            }
        }

        return $finalProperties;
    }


    /**
     * Create a component from its name
     */
    private function createComponent(string $componentName): ComponentInterface
    {
        return match (strtoupper($componentName)) {
            'VCALENDAR' => new VCalendar(),
            'VEVENT' => new VEvent(),
            'VTODO' => new VTodo(),
            'VJOURNAL' => new VJournal(),
            'VFREEBUSY' => new VFreeBusy(),
            'VTIMEZONE' => new VTimezone(),
            'STANDARD' => new Standard(),
            'DAYLIGHT' => new Daylight(),
            'VALARM' => new VAlarm(),
            'VAVAILABILITY' => new VAvailability(),
            'AVAILABLE' => new Available(),
            'PARTICIPANT' => new Participant(),
            default => new GenericComponent($componentName),
        };
    }

    /**
     * Format parsed values back to string for storage in GenericProperty
     */
    private function formatParsedValue(mixed $value, ?string $type = null): string
    {
        if ($value instanceof \DateTimeInterface) {
            if ($type === 'DATE') {
                return $value->format('Ymd');
            }
            
            // Default DATE-TIME format
            // Ensure timezone is handled correctly for formatting. UTC should append 'Z'.
            $timezone = $value->getTimezone();
            $formattedDate = $value->format('Ymd\THis');
            if ($timezone->getName() === 'UTC' || $timezone->getName() === 'Z') {
                $formattedDate .= 'Z';
            }
            return $formattedDate;
        }

        if ($value instanceof \DateInterval) {
            if ($type === 'UTC-OFFSET') {
                $sign = $value->invert ? '-' : '+';
                if ($value->s > 0) {
                    return sprintf('%s%02d%02d%02d', $sign, $value->h, $value->i, $value->s);
                }
                return sprintf('%s%02d%02d', $sign, $value->h, $value->i);
            }

            // Default DURATION format
            $parts = [];
            if ($value->y > 0) $parts[] = $value->y . 'Y';
            if ($value->m > 0) $parts[] = $value->m . 'M';
            if ($value->d > 0) $parts[] = $value->d . 'D';
            
            $timeParts = [];
            if ($value->h > 0) $timeParts[] = $value->h . 'H';
            if ($value->i > 0) $timeParts[] = $value->i . 'M';
            if ($value->s > 0) $timeParts[] = $value->s . 'S';
            
            $result = ($value->invert ? '-' : '') . 'P' . implode('', $parts);
            if (!empty($timeParts)) {
                $result .= 'T' . implode('', $timeParts);
            }
            // Handle zero duration case to avoid just 'P' or '-P'
            return ($result === 'P' || $result === '-P') ? 'PT0S' : $result;
        }

        if (is_array($value)) {
            // Handle cases like PERIOD (array of DateTime/Interval) or RECUR (array of rules)
            // PERIOD [start, end/duration]
            if (count($value) === 2 && !isset($value['FREQ']) && (
                $value[0] instanceof \DateTimeInterface || $value[0] instanceof \DateInterval ||
                $value[1] instanceof \DateTimeInterface || $value[1] instanceof \DateInterval)
            ) {
                 return $this->formatParsedValue($value[0]) . '/' . $this->formatParsedValue($value[1]);
            }
            
            // For other arrays (like BY* rules), join them with commas
            return implode(',', array_map(fn($v) => $this->formatParsedValue($v), $value));
        }

        if (is_object($value) && method_exists($value, 'toString')) {
            $str = $value->toString();
            return (string)$str;
        }

        return is_scalar($value) ? (string)$value : 'COMPLEX';
    }

    #[\Override]
    public function parseFile(string $filepath): VCalendar
    {
        $this->validateFilePath($filepath);
        $data = $this->readFile($filepath);
        $this->checkForXxe($data, $filepath);
        return $this->parse($data);
    }

    private function validateFilePath(string $filepath): void
    {
        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $filepath)) {
            throw new ParseException("URI scheme not allowed in file path: {$filepath}", ParseException::ERR_SECURITY_INVALID_SCHEME);
        }
    }

    private function readFile(string $filepath): string
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            throw new ParseException("File not found or unreadable: {$filepath}", ParseException::ERR_FILE_NOT_FOUND);
        }

        $data = file_get_contents($filepath);
        if ($data === false) {
            throw new ParseException("Failed to read file: {$filepath}", ParseException::ERR_FILE_NOT_FOUND);
        }

        return $data;
    }

    private function checkForXxe(string $data, string $filepath): void
    {
        // Basic check for common XXE indicators
        if (stripos($data, '<!ENTITY') !== false || stripos($data, '<!DOCTYPE') !== false) {
            throw new ParseException("Potential XXE attack detected in file: {$filepath}", ParseException::ERR_SECURITY_XXE_ATTEMPT);
        }
    }

    #[\Override]
    public function setStrict(bool $strict): void
    {
        $this->mode = $strict;
        $this->valueParserFactory->setStrict($strict);
    }

    /**
     * Get the current parsing mode.
     *
     * @return bool Parser::STRICT or Parser::LENIENT
     */
    public function getMode(): bool
    {
        return $this->mode;
    }

    /**
     * Get all errors and warnings collected during parsing.
     *
     * @return ValidationError[]
     */
    #[\Override]
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all warnings collected during parsing (alias for getErrors).
     *
     * @return ValidationError[]
     */
    public function getWarnings(): array
    {
        return $this->getErrors();
    }

    private function addError(
        string $code,
        string $message,
        string $component,
        ?string $property,
        ?string $line,
        int $lineNumber,
        ErrorSeverity $severity
    ): void {
        if ($this->mode === self::LENIENT || $severity === ErrorSeverity::WARNING) {
            $this->errors[] = new ValidationError($code, $message, $component, $property, $line, $lineNumber, $severity);
        }
    }

    public function setMaxDepth(int $maxDepth): void
    {
        $this->securityValidator->setMaxDepth($maxDepth);
    }

    public function getMaxDepth(): int
    {
        return $this->securityValidator->getMaxDepth();
    }

    public function getSecurityValidator(): SecurityValidator
    {
        return $this->securityValidator;
    }
}