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
        $currentComponent = $calendar;

        foreach ($contentLines as $contentLine) {
            // Re-parse with strict flag if needed (lexer only does basic parsing)
            // Note: The 'strict' flag here refers to the internal property of the parser,
            // which is now controlled by the $mode constructor argument.
            if ($this->mode === self::STRICT) {
                $contentLine = $this->propertyParser->parse($contentLine->getRawLine(), $contentLine->getContentLineNumber(), true);
            }

            $name = $contentLine->getName();

            // Handle BEGIN marker
            if ($name === 'BEGIN') {
                $componentName = $contentLine->getValue();

                // BEGIN:VCALENDAR uses the existing calendar object
                if (strtoupper($componentName) === 'VCALENDAR') {
                    $this->currentDepth++;
                    $this->securityValidator->checkDepth($this->currentDepth);
                    continue;
                }

                $component = $this->createComponent($componentName, $contentLine->getContentLineNumber());

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
                $currentComponent = $component;
                continue;
            }

            // Handle END marker
            if ($name === 'END') {
                $componentName = $contentLine->getValue();

                if (strtoupper($componentName) === 'VCALENDAR') {
                    $this->currentDepth--;
                    continue;
                }

                if (count($componentStack) > 1) {
                    $completedComponent = array_pop($componentStack);
                    $parentComponent = end($componentStack);

                    if ($completedComponent->getName() !== $componentName) {
                        $this->addError(
                            'ICAL-PARSE-006',
                            "END marker mismatch: expected {$completedComponent->getName()}, got {$componentName}",
                            $completedComponent->getName(),
                            $name,
                            $contentLine->getRawLine(),
                            $contentLine->getContentLineNumber(),
                            ErrorSeverity::ERROR
                        );
                    } else {
                        $parentComponent->addComponent($completedComponent);
                    }

                    $currentComponent = $parentComponent;
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
                && !str_starts_with($name, 'X-')) {
                continue;
            }

            $this->addPropertyToComponent($currentComponent, $contentLine, $name, $parameters, $value);
        }

        return $calendar;
    }

    /**
     * Create a component from its name
     */
    private function createComponent(string $componentName, int $lineNumber): ComponentInterface
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
            default => new GenericComponent($componentName),
        };
    }

    /**
     * Add a property to a component
     */
    private function addPropertyToComponent(ComponentInterface $component, ContentLine $contentLine, string $name, array $parameters, string $value): void
    {
        try {
            // Value parsing will respect the mode set by the parser
            $parsedValue = $this->valueParserFactory->parseValue($name, $value, $parameters);
            
            $propertyValue = $this->formatParsedValue($parsedValue);

            $property = new GenericProperty($name, new TextValue($propertyValue), $parameters);
            $component->addProperty($property);
        } catch (\Exception $e) {
            // Collect errors for lenient mode, throw for strict mode
            $this->addError(
                'ICAL-PARSE-006', // Generic parse error code
                $e->getMessage(),
                $component->getName(),
                $name,
                $contentLine->getRawLine(),
                $contentLine->getContentLineNumber(),
                // Use ERROR severity for strict, WARNING for lenient, unless it's a known specific error like date/time/summary
                ($this->mode === self::STRICT || 
                 ($name === 'DTSTART' || $name === 'DTEND' || $name === 'DURATION' || $name === 'RRULE' || $name === 'SUMMARY') // Specific properties to collect warnings for
                ) ? ErrorSeverity::ERROR : ErrorSeverity::WARNING
            );

            if ($this->mode === self::STRICT) {
                // If in strict mode, re-throw the exception
                if ($e instanceof ParseException) {
                    throw $e;
                }
                // Wrap other exceptions in ParseException for consistency in strict mode
                throw new ParseException($e->getMessage(), 'ICAL-PARSE-006', $contentLine->getContentLineNumber(), $contentLine->getRawLine(), $e);
            }
            // In lenient mode, we continue processing after adding the warning to errors list.
        }
    }

    /**
     * Format parsed values back to string for storage in GenericProperty
     */
    private function formatParsedValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            // Ensure timezone is handled correctly for formatting. UTC should append 'Z'.
            $timezone = $value->getTimezone();
            $formattedDate = $value->format('Ymd\THis');
            if ($timezone !== null && ($timezone->getName() === 'UTC' || $timezone->getName() === 'Z')) {
                $formattedDate .= 'Z';
            }
            return $formattedDate;
        }

        if ($value instanceof \DateInterval) {
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
            if (count($value) === 2 && !isset($value['FREQ']) && (
                $value[0] instanceof \DateTimeInterface || $value[0] instanceof \DateInterval ||
                $value[1] instanceof \DateTimeInterface || $value[1] instanceof \DateInterval)
            ) {
                 // If it's a single period [start, end/duration]
                 return $this->formatParsedValue($value[0]) . '/' . $this->formatParsedValue($value[1]);
            }
            
            // For other arrays (like BY* rules), join them with commas
            return implode(',', array_map(fn($v) => $this->formatParsedValue($v), $value));
        }

        return (string)$value;
    }

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

    // The setStrict method is now superseded by the $mode property set in the constructor.
    // Keeping it for backward compatibility might be an option, but it's better to deprecate or remove if not needed.
    // For now, let's make it set the mode.
    public function setStrict(bool $strict): void
    {
        $this->mode = $strict;
        $this->valueParserFactory->setStrict($strict);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Adds an error/warning to the parser's error list.
     * In strict mode, some errors might be re-thrown immediately.
     * In lenient mode, errors are collected and can be retrieved by getErrors().
     */
    private function addError(
        string $code,
        string $message,
        string $component,
        ?string $property,
        ?string $line,
        int $lineNumber,
        ErrorSeverity $severity
    ): void {
        // Only collect errors if in lenient mode or if it's a warning
        // or if it's a critical error that we want to report even in lenient mode.
        // For strict mode, we re-throw, so this only applies to lenient mode's collection.
        if ($this->mode === self::LENIENT || $severity === ErrorSeverity::WARNING) {
            $this->errors[] = new ValidationError($code, $message, $component, $property, $line, $lineNumber, $severity);
        }
        // Note: The specific handling for strict mode re-throwing is in addPropertyToComponent.
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