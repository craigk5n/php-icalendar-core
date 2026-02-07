# PHP iCalendar Core - Usage Guide

A comprehensive guide for developers using PHP iCalendar Core.

## Table of Contents

1. [Parsing Deep Dive](#parsing-deep-dive)
2. [Writing Deep Dive](#writing-deep-dive)
3. [Error Handling Guide](#error-handling-guide)
4. [Extension Guide](#extension-guide)
5. [Migration Guide](#migration-guide-from-other-libraries)
6. [Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)

---

## Parsing Deep Dive

### Understanding the Parsing Process

The library uses a multi-stage parsing process:

1. **Lexical Analysis** - Tokenizes raw iCalendar data into content lines
2. **Property Parsing** - Parses individual properties with parameters
3. **Value Parsing** - Converts string values to appropriate PHP types
4. **Component Building** - Constructs component hierarchy
5. **Validation** - Validates the parsed structure

```php
<?php
use Icalendar\Parser\Parser;

$parser = new Parser();

// The parser handles:
// - Line ending normalization (LF, CR, CRLF → CRLF)
// - Line unfolding (CRLF + space/tab continuation)
// - UTF-8 sequence preservation
// - RFC 6868 parameter encoding
// - Component nesting

$calendar = $parser->parse($icalData);
```

### Parsing Modes

#### Lenient Mode (Default)

In lenient mode, the parser makes a best effort to parse non-conformant iCalendar data:

```php
<?php
$parser = new Parser();
$parser->setStrict(false);

// Handles:
// - Missing required properties (with warnings)
// - Unknown properties (ignored)
// - Malformed parameters (best effort fix)
// - Invalid line endings (normalized)
```

#### Strict Mode

In strict mode, the parser throws exceptions on any non-compliance:

```php
<?php
$parser = new Parser();
$parser->setStrict(true);

// Throws ParseException for:
// - Unknown properties
// - Missing required properties
// - Invalid parameter formats
// - Any RFC 5545 violations
```

### Accessing Parsed Components

```php
<?php
$calendar = $parser->parse($icalData);

// Get all events
$events = $calendar->getComponents('VEVENT');

// Get all todos
$todos = $calendar->getComponents('VTODO');

// Get all journals
$journals = $calendar->getComponents('VJOURNAL');

// Get all free/busy entries
$freebusy = $calendar->getComponents('VFREEBUSY');

// Get timezone definitions
$timezones = $calendar->getComponents('VTIMEZONE');
```

### Accessing Properties

```php
<?php
foreach ($calendar->getComponents('VEVENT') as $event) {
    // Get single property (returns first occurrence)
    $summary = $event->getProperty('SUMMARY');
    if ($summary) {
        echo $summary->getValue();
    }
    
    // Get all occurrences of a property
    $descriptions = $event->getProperties('DESCRIPTION');
    
    // Access parameters
    $dtstart = $event->getProperty('DTSTART');
    if ($dtstart) {
        $tzid = $dtstart->getParameter('TZID');
        $value = $dtstart->getValue();
    }
}
```

### Working with Parsed Values

```php
<?php
$event = $calendar->getComponents('VEVENT')[0];

// DateTime properties
$dtstart = $event->getProperty('DTSTART')->getValue();
// Returns DateTimeImmutable object

// Duration properties
$duration = $event->getProperty('DURATION')->getValue();
// Returns DateInterval object

// Text properties
$summary = $event->getProperty('SUMMARY')->getValue();
// Returns string (unescaped)

// Boolean properties
$allDay = $event->getProperty('X-ALL-DAY')->getValue();
// Returns boolean

// Recurrence
$rrule = $event->getProperty('RRULE')->getValue();
// Returns RRule object
```

### Custom Parsing Callbacks

You can register callbacks for custom processing:

```php
<?php
use Icalendar\Parser\Parser;

$parser = new Parser();

// Register custom property handler
$parser->onProperty('X-CUSTOM', function($property, $component) {
    // Process custom property
    $component->setCustomData($property->getValue());
});
```

---

## Writing Deep Dive

### Understanding the Writing Process

The library uses a multi-stage writing process:

1. **Value Serialization** - Converts PHP values to iCalendar format
2. **Property Writing** - Formats properties with parameters
3. **Component Writing** - Generates BEGIN/END markers
4. **Line Folding** - Folds long lines per RFC 5545
5. **Output Generation** - Produces final iCalendar string

```php
<?php
use Icalendar\Writer\Writer;

$writer = new Writer();

// Configure line folding
$writer->setLineFolding(true, 75); // enabled, max 75 octets

// Generate output
$output = $writer->write($calendar);
```

### Value Serialization Details

#### DateTime Values

```php
<?php
use Icalendar\Component\VEvent;
use Icalendar\Writer\Writer;

$event = new VEvent();
$event->setStart(new DateTime('2026-02-10T10:00:00'));

// Output:
// DTSTART:20260210T100000

$event->setStart(new DateTime('2026-02-10T10:00:00', new DateTimeZone('UTC')));

// Output:
// DTSTART:20260210T100000Z

$event->setStart(new DateTime('2026-02-10T10:00:00', new DateTimeZone('America/New_York')));

// Output:
// DTSTART:20260210T100000
// TZID=America/New_York
```

#### Duration Values

```php
<?php
$event->setDuration(new DateInterval('PT1H30M'));

// Output:
// DURATION:PT1H30M

$event->setDuration(new DateInterval('P2D'));

// Output:
// DURATION:P2D
```

#### Text Values

```php
<?php
$event->setDescription("Line 1\nLine 2\nLine 3");

// Output (with RFC 5545 escaping):
// DESCRIPTION:Line 1\\nLine 2\\nLine 3

$event->setSummary("Hello; World, \"Test\"");

// Output:
// SUMMARY:Hello\\; World\\, \"Test\"
```

### Parameter Handling

```php
<?php
$event->setAttendee('mailto:user@example.com', [
    'CN' => 'John Doe',
    'RSVP' => 'TRUE',
    'ROLE' => 'REQ-PARTICIPANT',
]);

// Output:
// ATTENDEE:mailto:user@example.com;CN=John Doe;RSVP=TRUE;ROLE=REQ-PARTICIPANT
```

### Line Folding

The library automatically folds long lines:

```php
<?php
// Disable folding for debugging
$writer->setLineFolding(false);

// Enable with custom length
$writer->setLineFolding(true, 100); // 100 octets
```

### Performance Optimization

```php
<?php
// For large calendars, write incrementally
$writer = new Writer();

// Write components individually
foreach ($calendar->getComponents() as $component) {
    $componentOutput = $writer->writeComponent($component);
    // Process component output
}
```

---

## Error Handling Guide

### Exception Types

The library uses three exception types:

1. **ParseException** - Parsing errors (invalid format, syntax)
2. **ValidationException** - Validation errors (missing required, invalid values)
3. **InvalidDataException** - Invalid data errors (type mismatches)

```php
<?php
use Icalendar\Exception\ParseException;
use Icalendar\Exception\ValidationException;
use Icalendar\Exception\InvalidDataException;

try {
    $calendar = $parser->parse($icalData);
} catch (ParseException $e) {
    // Handle parsing errors
    echo "Parse error [{$e->getErrorCode()}]: {$e->getMessage()}\n";
    echo "Line {$e->getLineNumber()}: {$e->getLine()}\n";
} catch (ValidationException $e) {
    // Handle validation errors
    echo "Validation error [{$e->getErrorCode()}]: {$e->getMessage()}\n";
} catch (InvalidDataException $e) {
    // Handle invalid data
    echo "Invalid data [{$e->getErrorCode()}]: {$e->getMessage()}\n";
}
```

### Error Codes Reference

```php
<?php
use Icalendar\Exception\ParseException;

// Parser errors
ParseException::ERR_INVALID_LINE_ENDING;      // ICAL-PARSE-001
ParseException::ERR_MALFORMED_FOLDING;         // ICAL-PARSE-002
ParseException::ERR_LINE_TOO_LONG;            // ICAL-PARSE-003
ParseException::ERR_INVALID_PROPERTY_FORMAT;    // ICAL-PARSE-006
ParseException::ERR_INVALID_PROPERTY_NAME;    // ICAL-PARSE-007
ParseException::ERR_INVALID_PARAMETER_FORMAT;   // ICAL-PARSE-008
ParseException::ERR_UNCLOSED_QUOTED_STRING;    // ICAL-PARSE-009

// Data type errors
ParseException::ERR_INVALID_DATE;            // ICAL-TYPE-004
ParseException::ERR_INVALID_DATE_TIME;        // ICAL-TYPE-005
ParseException::ERR_INVALID_DURATION;          // ICAL-TYPE-006
ParseException::ERR_INVALID_TEXT;             // ICAL-TYPE-011
```

### Non-Fatal Errors

The parser collects non-fatal errors that don't prevent parsing:

```php
<?php
$parser = new Parser();
$parser->setStrict(false); // Lenient mode

$calendar = $parser->parse($icalData);

// Check for non-fatal warnings
$errors = $parser->getErrors();

foreach ($errors as $error) {
    switch ($error->severity) {
        case ErrorSeverity::WARNING:
            // Log warning
            error_log("Warning: {$error->code} - {$error->message}");
            break;
        case ErrorSeverity::ERROR:
            // Log error
            error_log("Error: {$error->code} - {$error->message}");
            break;
        case ErrorSeverity::FATAL:
            // Should not happen in lenient mode
            error_log("Fatal: {$error->code} - {$error->message}");
            break;
    }
}
```

### Custom Error Handling

```php
<?php
use Icalendar\Parser\Parser;
use Icalendar\Validation\Validator;
use Icalendar\Validation\ErrorSeverity;

class CustomErrorHandler
{
    public function parseWithLogging(string $icalData): ?VCalendar
    {
        $parser = new Parser();
        $parser->setStrict(false);
        
        try {
            $calendar = $parser->parse($icalData);
            
            // Validate the result
            $validator = new Validator();
            $errors = $validator->validate($calendar);
            
            // Log all errors
            $this->logValidationErrors($errors);
            
            return $calendar;
        } catch (ParseException $e) {
            $this->handleParseError($e);
            return null;
        }
    }
    
    private function logValidationErrors(array $errors): void
    {
        $warnings = array_filter($errors, fn($e) => $e->severity === ErrorSeverity::WARNING);
        $errorsList = array_filter($errors, fn($e) => $e->severity === ErrorSeverity::ERROR);
        
        if (!empty($warnings)) {
            error_log(count($warnings) . " warnings during validation");
        }
        
        if (!empty($errorsList)) {
            error_log(count($errorsList) . " errors during validation");
            foreach ($errorsList as $error) {
                error_log("  - {$error->code}: {$error->message}");
            }
        }
    }
    
    private function handleParseError(ParseException $e): void
    {
        error_log("Parse failed: {$e->getErrorCode()} - {$e->getMessage()}");
        error_log("  Line {$e->getLineNumber()}: {$e->getLine()}");
    }
}
```

---

## Extension Guide

### Creating Custom Components

You can extend the library with custom components:

```php
<?php
namespace MyApp\Icalendar\Component;

use Icalendar\Component\AbstractComponent;

class VMeeting extends AbstractComponent
{
    protected function getRequiredProperties(): array
    {
        return ['DTSTAMP', 'UID', 'SUMMARY'];
    }
    
    protected function getAllowedProperties(): array
    {
        return array_merge(parent::getAllowedProperties(), [
            'MEETING-ID',
            'MEETING-TYPE',
            'LOCATION',
        ]);
    }
    
    public function getName(): string
    {
        return 'VMEETING';
    }
    
    // Custom methods
    public function setMeetingId(string $id): self
    {
        $this->setProperty('MEETING-ID', $id);
        return $this;
    }
    
    public function getMeetingId(): ?string
    {
        $prop = $this->getProperty('MEETING-ID');
        return $prop ? $prop->getValue() : null;
    }
}
```

### Creating Custom Value Types

```php
<?php
namespace MyApp\Icalendar\Value;

use Icalendar\Parser\ValueParser\ValueParserInterface;

class EncryptedValue implements ValueParserInterface
{
    private string $encryptionMethod;
    
    public function parse(string $value, array $parameters = []): EncryptedData
    {
        // Parse encrypted data
        return new EncryptedData($value, $this->encryptionMethod);
    }
    
    public function getType(): string
    {
        return 'ENCRYPTED';
    }
}
```

### Creating Custom Property Types

```php
<?php
namespace MyApp\Icalendar\Property;

use Icalendar\Property\AbstractProperty;

class EncryptedProperty extends AbstractProperty
{
    public function getName(): string
    {
        return 'ENCRYPTED-DATA';
    }
    
    protected function getValueType(): string
    {
        return 'ENCRYPTED';
    }
    
    public function getAllowedParameters(): array
    {
        return ['ENCRYPTION-METHOD', 'KEY-ID'];
    }
}
```

### Registering Custom Extensions

```php
<?php
use Icalendar\Parser\Parser;
use MyApp\Icalendar\Component\VMeeting;
use MyApp\Icalendar\Value\EncryptedValue;

// Register custom component
Parser::registerComponent('VMEETING', VMeeting::class);

// Register custom value parser
Parser::registerValueParser('ENCRYPTED', EncryptedValue::class);
```

### Using Custom Extensions

```php
<?php
$parser = new Parser();
$parser->registerComponent('VMEETING', VMeeting::class);
$parser->registerValueParser('ENCRYPTED', EncryptedValue::class);

$calendar = $parser->parse($icalData);

// Access custom component
foreach ($calendar->getComponents('VMEETING') as $meeting) {
    echo $meeting->getMeetingId();
}
```

---

## Migration Guide from Other Libraries

### Migration from ` League/calendar`

```php
<?php
// Old League/Calendar code
// use League\Calendar\Calendar;
// $calendar = new Calendar();
// $event = $calendar->event();

// New PHP iCalendar Core code
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;

// Create calendar
$calendar = new VCalendar();
$calendar->setProductId('-//MyApp//MyCalendar//EN');
$calendar->setVersion('2.0');

// Create event
$event = new VEvent();
$event->setUid('event-' . uniqid() . '@example.com');
$event->setSummary('Event Title');
$event->setStart(new DateTime('2026-02-10T10:00:00'));
$event->setEnd(new DateTime('2026-02-10T11:00:00'));

$calendar->addComponent($event);
```

### Migration from `simplecalendar`

```php
<?php
// Old simplecalendar code
// $calendar = new SimpleCalendar();
// $calendar->addEvent('Event', '2026-02-10', 'Description');

// New PHP iCalendar Core code
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;

$calendar = new VCalendar();
$calendar->setProductId('-//MyApp//MyCalendar//EN');
$calendar->setVersion('2.0');

$event = new VEvent();
$event->setUid('event-' . uniqid() . '@example.com');
$event->setSummary('Event');
$event->setStart(new DateTime('2026-02-10'));
$event->setDescription('Description');
// Note: For all-day events, use VALUE=DATE instead of DATE-TIME

$calendar->addComponent($event);
```

### Migration from `eluceo/ical`

```php
<?php
// Old eluceo/ical code
// $event = new \Eluceo\iCal\Component\Event('2026-02-10');
// $event->setSummary('Event');
// $calendar = new \Eluceo\iCal\Component\Calendar([$event]);

// New PHP iCalendar Core code
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;

$calendar = new VCalendar();
$calendar->setProductId('-//MyApp//MyCalendar//EN');
$calendar->setVersion('2.0');

$event = new VEvent();
$event->setUid('event-' . uniqid() . '@example.com');
$event->setSummary('Event');
$event->setStart(new DateTime('2026-02-10T10:00:00'));

$calendar->addComponent($event);
```

### Key Differences

| Feature | League/calendar | simplecalendar | eluceo/ical | PHP iCalendar |
|---------|-----------------|----------------|--------------|----------------|
| Namespace | `League\Calendar` | `SimpleCalendar` | `Eluceo\iCal` | `Icalendar\*` |
| Component Base | `CalendarComponent` | Base class | Component\* | AbstractComponent |
| Event Creation | `->event()` | `->addEvent()` | `new Event()` | `new VEvent()` |
| Timezone Handling | Automatic | Manual | Manual | Full support |
| Recurrence | Limited | No | Basic | Complete RRULE |
| Validation | No | No | Basic | Comprehensive |
| Type Safety | No | No | Partial | Full PHP 8.1 |

### Common Migration Patterns

#### Setting Event Properties

```php
// Old: $event->setProperty('SUMMARY', 'My Event');
// New:
$event->setSummary('My Event');

// Old: $event->setDescription('Description');
// New:
$event->setDescription('Description');

// Old: $event->setLocation('Location');
// New:
$event->setLocation('Location');

// Old: $event->setCategories(['Category1', 'Category2']);
// New:
$event->setCategories(['Category1', 'Category2']);
```

#### Adding Attendees

```php
// Old: $event->setAttendees(['user@example.com']);
// New:
$event->setAttendee('mailto:user@example.com');
// Or with parameters:
$event->setAttendee('mailto:user@example.com', [
    'CN' => 'John Doe',
    'ROLE' => 'REQ-PARTICIPANT',
]);
```

#### Setting Recurrence

```php
// Old: $event->setRecurrence('FREQ=WEEKLY');
// New:
$event->setRRule('FREQ=WEEKLY;BYDAY=MO,WE,FR');
```

#### Adding Alarms

```php
// Old: $event->setAlarm('15', 'Reminder');
// New:
$alarm = new VAlarm();
$alarm->setAction('DISPLAY');
$alarm->setDescription('Reminder');
$alarm->setTrigger(new DateInterval('PT15M'));
$event->addAlarm($alarm);
```

---

## Best Practices

### 1. Always Generate Unique IDs

```php
<?php
// Good: Generate UID
$event->setUid(uniqid('event-', true) . '@' . $_SERVER['HTTP_HOST']);

// Bad: Hardcoded UID
$event->setUid('event-123');
```

### 2. Use Proper Timezones

```php
<?php
// Good: Use timezone-aware DateTime
$event->setStart(new DateTime('2026-02-10T10:00:00', new DateTimeZone('America/New_York')));

// Bad: Use local time without timezone
$event->setStart(new DateTime('2026-02-10T10:00:00'));
```

### 3. Validate Before Output

```php
<?php
$writer = new Writer();
$calendar = $parser->parse($icalData);

// Always validate
$validator = new Validator();
$errors = $validator->validate($calendar);

if (!empty($errors)) {
    // Handle validation errors before writing
    throw new RuntimeException('Invalid calendar');
}

$output = $writer->write($calendar);
```

### 4. Handle Errors Gracefully

```php
<?php
try {
    $calendar = $parser->parse($icalData);
} catch (ParseException $e) {
    // Log with context
    error_log("Parse error: {$e->getErrorCode()} - {$e->getMessage()}");
    
    // Return user-friendly error
    return 'Unable to parse calendar file. Please check the format.';
}
```

### 5. Use Streaming for Large Files

```php
<?php
// Good: Use streaming for large files
$parser = new Parser();
foreach ($parser->tokenizeFile('large-calendar.ics') as $line) {
    // Process incrementally
}

// Bad: Load entire file into memory
$icalData = file_get_contents('large-calendar.ics');
$calendar = $parser->parse($icalData);
```

### 6. Set Appropriate Timeouts

```php
<?php
// For large calendars, increase timeout
set_time_limit(300); // 5 minutes

// For memory-intensive operations
ini_set('memory_limit', '256M');
```

---

## Troubleshooting

### Common Issues

#### Issue: Timezone Not Working

**Problem:** Events are in wrong timezone.

**Solution:** Use timezone-aware DateTime objects:

```php
<?php
// Wrong:
$event->setStart(new DateTime('2026-02-10T10:00:00'));

// Right:
$event->setStart(new DateTime('2026-02-10T10:00:00', new DateTimeZone('America/New_York')));
```

#### Issue: Recurrence Not Generating

**Problem:** Recurring events show only first occurrence.

**Solution:** Use the RecurrenceGenerator:

```php
<?php
use Icalendar\Recurrence\RecurrenceGenerator;

$rrule = $event->getProperty('RRULE')->getValue();
$start = $event->getProperty('DTSTART')->getValue();

$generator = new RecurrenceGenerator();
$occurrences = $generator->generate($rrule, $start, new DateTime('2026-12-31'));

foreach ($occurrences as $occurrence) {
    echo $occurrence->format('Y-m-d H:i:s') . "\n";
}
```

#### Issue: Special Characters Not Escaped

**Problem:** Semicolons and commas in text are not escaped.

**Solution:** The library handles escaping automatically. If manually setting values:

```php
<?php
// Wrong: Manual escaping
$event->setDescription('Text; with, special \\ characters');

// Right: Let the library handle escaping
$event->setDescription('Text; with, special \ characters');
```

#### Issue: Memory Usage High

**Problem:** Parsing large files uses too much memory.

**Solution:** Use streaming:

```php
<?php
$parser = new Parser();

// Use streaming instead of loading entire file
foreach ($parser->tokenizeFile('large-file.ics') as $line) {
    // Process line-by-line
}
```

#### Issue: Validation Errors on Valid Data

**Problem:** Validation fails on RFC 5545 compliant data.

**Solution:** Check for parser errors and use lenient mode:

```php
<?php
$parser = new Parser();
$parser->setStrict(false);

$calendar = $parser->parse($icalData);

// Check parser errors
$errors = $parser->getErrors();
if (!empty($errors)) {
    foreach ($errors as $error) {
        error_log("Parser warning: {$error->message}");
    }
}
```

### Debugging Tips

#### Enable Verbose Logging

```php
<?php
// Enable debug logging
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Log parser operations
$parser = new Parser();
// ... operations
```

#### Check Generated Output

```php
<?php
// Generate and inspect output
$writer = new Writer();
$output = $writer->write($calendar);

// Log or output for inspection
file_put_contents('debug-output.ics', $output);
```

#### Validate Output

```php
<?php
// Parse generated output to verify correctness
$validator = new Validator();
$outputCalendar = $parser->parse($writer->write($calendar));
$errors = $validator->validate($outputCalendar);

if (empty($errors)) {
    echo "Output is valid!\n";
} else {
    foreach ($errors as $error) {
        echo "Error: {$error->code} - {$error->message}\n";
    }
}
```

---

## Additional Resources

- **API Documentation**: See `/docs/api` for complete API reference
- **Examples**: See `/examples` for comprehensive code examples
- **Tests**: See `/tests` for implementation patterns
- **RFC 5545**: https://tools.ietf.org/html/rfc5545
- **GitHub Issues**: Report bugs and request features

---

**Note**: This guide is continuously updated. Check the GitHub repository for the latest version.
