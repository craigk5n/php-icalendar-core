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
use Icalendar\Validation\Validator;
use Icalendar\Validation\ValidatorInterface;
use Icalendar\Validation\ValidationResult;
use Icalendar\Parser\ValueParser\ValueParserFactory;

class Parser implements ParserInterface
{
    public const STRICT = true;
    public const LENIENT = false;

    private bool $mode;
    private bool $enableValidation = false;
    private ?ValidatorInterface $validator = null;
    private ?ValidationResult $validationErrors = null;

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
        $this->validationErrors = null;

        $lexer = new Lexer();
        $lexer->setStrict($this->mode);

        // Consume the generator directly rather than collecting every
        // ContentLine first: on a large calendar that intermediate array is one
        // object per line and dominates the parse's memory.
        $calendar = $this->buildCalendar($lexer->tokenize($data));

        // Must follow the build: the lexer only records warnings as its
        // generator is consumed.
        $this->transferLexerWarnings($lexer);

        if ($this->enableValidation) {
            $this->runValidation($calendar);
        }

        return $calendar;
    }

    public function withValidation(?ValidatorInterface $validator = null): self
    {
        $this->enableValidation = true;
        $this->validator = $validator;
        return $this;
    }

    public function getValidationErrors(): ValidationResult
    {
        return $this->validationErrors ?? ValidationResult::empty();
    }

    private function runValidation(VCalendar $calendar): void
    {
        $validator = $this->validator ?? new Validator();
        $this->validationErrors = $validator->validate($calendar);

        if ($this->mode === self::STRICT && $this->validationErrors->hasErrors()) {
            $firstError = $this->validationErrors->firstError();
            if ($firstError !== null) {
                throw new ValidationException(
                    $firstError->message,
                    $firstError->code
                );
            }
        }
    }

    /**
     * Build a VCalendar from content lines
     *
     * Accepts any iterable so a lexer generator can be consumed line by line
     * without materialising every ContentLine first.
     *
     * @param iterable<ContentLine> $contentLines
     * @return VCalendar
     */
    private function buildCalendar(iterable $contentLines): VCalendar
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
                if ($this->isTextListProperty($name)) {
                    // A comma-separated list of TEXT (RFC 5545 §3.8.1.2). Split on
                    // the unescaped separators before unescaping each item, so a
                    // literal comma inside a value (\,) is not mistaken for a list
                    // separator -- the whole-value unescape below would lose that.
                    $property = new GenericProperty(
                        $name,
                        \Icalendar\Value\TextListValue::fromRawValue($value),
                        $parameters
                    );
                } else {
                    $parser = $this->valueParserFactory->getParserForProperty($name, $parameters);
                    $parsedValue = $parser->parse($value, $parameters);
                    $type = $parser->getType();
                    // Take UTC-ness from the source rather than the parsed value's
                    // timezone: only a trailing 'Z' means UTC. PERIOD passes null so
                    // its halves keep using the timezone fallback (§3.3.9 makes them UTC).
                    $sourceIsUtc = $type === 'DATE-TIME' ? str_ends_with($value, 'Z') : null;
                    $propertyValue = $this->formatParsedValue($parsedValue, $type, $sourceIsUtc);
                    $property = new GenericProperty($name, new \Icalendar\Value\GenericValue($propertyValue, $type), $parameters);
                }

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
     * Whether a property's value is a comma-separated list of TEXT values that
     * must preserve its list structure through parsing (RFC 5545 §3.8.1.2).
     *
     * Only CATEGORIES is handled here -- the property with a list-aware setter
     * and getter; RESOURCES shares the grammar and can join the set later.
     */
    private function isTextListProperty(string $name): bool
    {
        return strtoupper($name) === 'CATEGORIES';
    }

    /**
     * Format parsed values back to string for storage in GenericProperty
     *
     * @param bool|null $sourceIsUtc Whether the source value carried a trailing
     *   'Z'. Pass null when the source form is unknown, to fall back to reading
     *   the value's timezone. UTC-ness must be recorded, not inferred: a
     *   floating DATE-TIME (RFC 5545 §3.3.5) is built by
     *   DateTimeParser::parseLocal() without an explicit zone, so it picks up
     *   PHP's date.timezone -- and inferring from that name promoted floating
     *   values to UTC on any host configured as UTC.
     */
    private function formatParsedValue(mixed $value, ?string $type = null, ?bool $sourceIsUtc = null): string
    {
        if ($value instanceof \DateTimeInterface) {
            if ($type === 'DATE') {
                return $value->format('Ymd');
            }

            $formattedDate = $value->format('Ymd\THis');
            $isUtc = $sourceIsUtc ?? in_array($value->getTimezone()->getName(), ['UTC', 'Z'], true);

            return $isUtc ? $formattedDate . 'Z' : $formattedDate;
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

        // RFC 5545 §3.3.2 spells booleans TRUE and FALSE. The (string) cast below
        // renders them '1' and '' -- the latter destroying the value entirely.
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return is_scalar($value) ? (string)$value : 'COMPLEX';
    }

    #[\Override]
    public function parseFile(string $filepath): VCalendar
    {
        $this->validateFilePath($filepath);
        $this->checkFileForXxe($filepath);

        $this->errors = [];
        $this->currentDepth = 0;
        $this->validationErrors = null;

        $lexer = new Lexer();
        $lexer->setStrict($this->mode);

        // Stream the file rather than reading it whole. Lexer::tokenizeFile()
        // exists for exactly this and previously had no production caller, so
        // every file parse cost memory proportional to the file's size.
        $calendar = $this->buildCalendar($lexer->tokenizeFile($filepath));

        $this->transferLexerWarnings($lexer);

        if ($this->enableValidation) {
            $this->runValidation($calendar);
        }

        return $calendar;
    }

    /**
     * Scan a file for XXE markers without holding it in memory.
     *
     * Read in chunks, carrying the tail of each into the next so a marker split
     * across a chunk boundary is still found -- the whole-file scan this
     * replaces could not miss one, and neither may this.
     *
     * @throws ParseException if a marker is present
     */
    private function checkFileForXxe(string $filepath): void
    {
        $handle = @fopen($filepath, 'r');
        if ($handle === false) {
            // Leave the diagnostic to the lexer, which reports not-found and
            // unreadable separately.
            return;
        }

        // One less than the longest marker, so any split still overlaps.
        $overlap = max(strlen('<!ENTITY'), strlen('<!DOCTYPE')) - 1;
        $carry = '';

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, 8192);
                if ($chunk === false) {
                    break;
                }

                $window = $carry . $chunk;
                if (stripos($window, '<!ENTITY') !== false || stripos($window, '<!DOCTYPE') !== false) {
                    throw new ParseException(
                        "Potential XXE attack detected in file: {$filepath}",
                        ParseException::ERR_SECURITY_XXE_ATTEMPT
                    );
                }

                $carry = substr($window, -$overlap);
            }
        } finally {
            fclose($handle);
        }
    }

    private function validateFilePath(string $filepath): void
    {
        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $filepath)) {
            throw new ParseException("URI scheme not allowed in file path: {$filepath}", ParseException::ERR_SECURITY_INVALID_SCHEME);
        }
    }

    // readFile() and checkForXxe() were removed with the switch to streaming:
    // the first slurped the whole file, and the second could only scan a string
    // it had all of. Lexer::tokenizeFile() now reports not-found and unreadable
    // (ICAL-IO-001 / ICAL-IO-003), and checkFileForXxe() scans in chunks.

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

    /**
     * Transfer warnings collected by the Lexer into the Parser's error list.
     */
    private function transferLexerWarnings(Lexer $lexer): void
    {
        foreach ($lexer->getWarnings() as $warning) {
            $this->errors[] = new ValidationError(
                'ICAL-PARSE-006',
                $warning['message'],
                'VCALENDAR',
                null,
                $warning['line'],
                $warning['lineNumber'],
                ErrorSeverity::WARNING
            );
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