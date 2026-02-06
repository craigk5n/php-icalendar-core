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
use Icalendar\Exception\ParseException;
use Icalendar\Property\GenericProperty;
use Icalendar\Value\TextValue;
use Icalendar\Exception\ValidationException;
use Icalendar\Validation\SecurityValidator;
use Icalendar\Validation\ValidationError;
use Icalendar\Validation\ErrorSeverity;

/**
 * Main parser implementation
 *
 * Parses iCalendar data into component structures.
 */
class Parser implements ParserInterface
{
    private bool $strict = false;

    /** @var ValidationError[] */
    private array $errors = [];

    private SecurityValidator $securityValidator;

    private int $currentDepth = 0;

    private PropertyParser $propertyParser;

    public function __construct()
    {
        $this->securityValidator = new SecurityValidator();
        $this->propertyParser = new PropertyParser();
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
            $name = $contentLine->getName();

            // Handle BEGIN marker
            if ($name === 'BEGIN') {
                $componentName = $contentLine->getValue();

                // BEGIN:VCALENDAR uses the existing calendar object
                if (strtoupper($componentName) === 'VCALENDAR') {
                    $this->currentDepth++;
                    $this->securityValidator->checkDepth($this->currentDepth);
                    // Calendar is already on the stack; just continue
                    continue;
                }

                $component = $this->createComponent($componentName, $contentLine->getContentLineNumber());

                if ($component === null) {
                    $this->addError(
                        'ICAL-PARSE-007',
                        "Unknown component type: {$componentName}",
                        'UNKNOWN',
                        $name,
                        $contentLine->getRawLine(),
                        $contentLine->getContentLineNumber(),
                        ErrorSeverity::WARNING
                    );
                    continue;
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

                // END:VCALENDAR just decrements depth
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
                        // Add completed component to parent
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

            // Skip non-VCALENDAR properties that appear at the root level (outside any component)
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
     *
     * @param string $componentName The component name
     * @param int $lineNumber The line number for error reporting
     * @return ComponentInterface|null
     */
    private function createComponent(string $componentName, int $lineNumber): ?ComponentInterface
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
            default => null,
        };
    }

    /**
     * Add a property to a component
     *
     * @param ComponentInterface $component
     * @param ContentLine $contentLine
     * @param string $name
     * @param array $parameters
     * @param string $value
     */
    private function addPropertyToComponent(ComponentInterface $component, ContentLine $contentLine, string $name, array $parameters, string $value): void
    {
        try {
            $property = new GenericProperty($name, new TextValue($value), $parameters);
            $component->addProperty($property);
        } catch (\Exception $e) {
            $this->addError(
                'ICAL-PARSE-006',
                $e->getMessage(),
                $component->getName(),
                $name,
                $contentLine->getRawLine(),
                $contentLine->getContentLineNumber(),
                $this->strict ? ErrorSeverity::ERROR : ErrorSeverity::WARNING
            );
        }
    }

    public function parseFile(string $filepath): VCalendar
    {
        $this->validateFilePath($filepath);

        $data = $this->readFile($filepath);

        $this->checkForXxe($data, $filepath);

        return $this->parse($data);
    }

    /**
     * Validate file path for security
     */
    private function validateFilePath(string $filepath): void
    {
        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $filepath)) {
            throw new ParseException(
                "URI scheme not allowed in file path: {$filepath}",
                ParseException::ERR_SECURITY_INVALID_SCHEME
            );
        }
    }

    /**
     * Read file contents
     */
    private function readFile(string $filepath): string
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            throw new ParseException(
                "File not found or unreadable: {$filepath}",
                ParseException::ERR_FILE_NOT_FOUND
            );
        }

        $data = file_get_contents($filepath);
        if ($data === false) {
            throw new ParseException(
                "Failed to read file: {$filepath}",
                ParseException::ERR_FILE_NOT_FOUND
            );
        }

        return $data;
    }

    /**
     * Check for XXE attempts in file content
     */
    private function checkForXxe(string $data, string $filepath): void
    {
        if (stripos($data, '<!ENTITY') !== false || stripos($data, '<!DOCTYPE') !== false) {
            throw new ParseException(
                "Potential XXE attack detected in file: {$filepath}",
                ParseException::ERR_SECURITY_XXE_ATTEMPT
            );
        }
    }

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add a validation error
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
        $this->errors[] = new ValidationError(
            $code,
            $message,
            $component,
            $property,
            $line,
            $lineNumber,
            $severity
        );
    }

    /**
     * Set maximum nesting depth for security
     *
     * Limits how deeply nested iCalendar components can be to prevent
     * potential security issues with deeply nested structures.
     *
     * @param int $maxDepth Maximum allowed nesting depth (default is typically 50)
     * @return void
     */
    public function setMaxDepth(int $maxDepth): void
    {
        $this->securityValidator->setMaxDepth($maxDepth);
    }

    /**
     * Get maximum nesting depth
     *
     * @return int The current maximum allowed nesting depth
     */
    public function getMaxDepth(): int
    {
        return $this->securityValidator->getMaxDepth();
    }

    /**
     * Get the security validator instance
     *
     * Provides access to the underlying SecurityValidator for advanced
     * configuration of security policies and settings.
     *
     * @return SecurityValidator The security validator instance
     */
    public function getSecurityValidator(): SecurityValidator
    {
        return $this->securityValidator;
    }
}
