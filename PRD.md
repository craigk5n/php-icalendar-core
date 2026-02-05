# Product Requirements Document (PRD)
# PHP iCalendar Parser and Writer Library

**Version:** 1.0.0  
**Date:** 2026-02-05  
**Status:** Draft for Review  

---

## 1. Executive Summary

This document outlines the requirements for a PHP library that provides complete iCalendar parsing and writing capabilities. The library will serve as the core import/export engine for a PHP calendar application, ensuring full compliance with RFC 5545 and related specifications.

---

## 2. Scope and Objectives

### 2.1 Scope
- Parse and write RFC 5545 compliant iCalendar data
- Support all standard calendar components (VEVENT, VTODO, VJOURNAL, VFREEBUSY, VTIMEZONE, VALARM)
- Handle all property value data types defined in RFC 5545
- Implement proper folding/unfolding of content lines
- Support property parameters and encoding schemes
- Validate conformance to iCalendar specifications
- Gracefully handle malformed input with detailed error reporting

### 2.2 Key Extensions (Future Phase 2)
- RFC 5546: iTIP scheduling methods
- RFC 6868: Parameter value encoding
- RFC 7529: Non-Gregorian recurrence rules (RSCALE)
- RFC 7953: VAVAILABILITY component
- RFC 7986: New properties (COLOR, IMAGE, CONFERENCE, etc.)
- RFC 9073 & 9074: Structured data and relationships

---

## 3. RFC Compliance Requirements

### 3.1 Mandatory Standards (Phase 1)
| RFC | Title | Implementation Level |
|-----|-------|---------------------|
| RFC 5545 | Internet Calendaring and Scheduling Core Object Specification (iCalendar) | Full compliance required |
| RFC 6868 | Parameter Value Encoding in iCalendar and vCard | Required for robust parsing |

### 3.2 Extended Standards (Phase 2)
| RFC | Title | Implementation Level |
|-----|-------|---------------------|
| RFC 5546 | iCalendar Transport-Independent Interoperability Protocol (iTIP) | Scheduling method support |
| RFC 7529 | Non-Gregorian Recurrence Rules in iCalendar | RSCALE support |
| RFC 7953 | Calendar Availability | VAVAILABILITY component |
| RFC 7986 | New Properties for iCalendar | Extended properties |

---

## 4. Functional Requirements

### 4.1 Core Parsing Requirements

#### 4.1.1 Content Line Processing
- **FCR-001**: Parse content lines delimited by CRLF sequences
- **FCR-002**: Handle line folding (CRLF followed by space/tab) with proper unfolding
- **FCR-003**: Support 75-octet line folding as per RFC 5545 section 3.1
- **FCR-004**: Handle UTF-8 multi-byte sequences correctly when folding/unfolding
- **FCR-005**: Preserve exact byte sequences for binary content

#### 4.1.2 Property Parsing
- **FCR-006**: Parse properties in format: `name *(";" param) ":" value`
- **FCR-007**: Support property names as IANA tokens or experimental X-names
- **FCR-008**: Parse property parameters in format: `param-name "=" param-value *("," param-value)`
- **FCR-009**: Handle quoted-string parameter values for values containing COLON, SEMICOLON, or COMMA
- **FCR-010**: Apply RFC 6868 decoding for parameter values (^n, ^^, ^')
- **FCR-011**: Support multi-valued parameters separated by COMMA
- **FCR-012**: Parse property values according to their declared VALUE type

#### 4.1.3 Data Type Parsing
- **FCR-013**: Parse all RFC 5545 value data types:
  - **BINARY**: Base64 encoded binary data
  - **BOOLEAN**: TRUE/FALSE values
  - **CAL-ADDRESS**: URI with mailto: scheme for calendar users
  - **DATE**: YYYYMMDD format
  - **DATE-TIME**: YYYYMMDDTHHMMSS[Z] format (local, UTC, or TZID referenced)
  - **DURATION**: P[n]W or P[n]DT[n]H[n]M[n]S format
  - **FLOAT**: Decimal floating point
  - **INTEGER**: Signed integer
  - **PERIOD**: explicit duration or start/end pair
  - **RECUR**: RRULE recurrence pattern (FREQ, INTERVAL, etc.)
  - **TEXT**: Escaped text (\\, \;, \,, \n, \\N)
  - **TIME**: HHMMSS[Z] format
  - **URI**: Uniform Resource Identifier
  - **UTC-OFFSET**: [+/-]HHMM[SS] format

### 4.2 Component Support Requirements

#### 4.2.1 VCALENDAR Component (Container)
- **FCR-014**: Parse root VCALENDAR component with required PRODID and VERSION
- **FCR-015**: Support CALSCALE property (GREGORIAN default)
- **FCR-016**: Support METHOD property for iTIP transactions
- **FCR-017**: Support X- properties and IANA-registered properties

#### 4.2.2 VEVENT Component
- **FCR-018**: Parse required properties: DTSTAMP, UID
- **FCR-019**: Parse recommended properties: DTSTART, CLASS, CREATED, DESCRIPTION, GEO, LAST-MODIFIED, LOCATION, ORGANIZER, PRIORITY, SEQUENCE, STATUS, SUMMARY, TRANSP, URL, RECURRENCE-ID, RRULE, DTEND, DURATION
- **FCR-020**: Support all descriptive properties: ATTACH, CATEGORIES, COMMENT
- **FCR-021**: Support all relationship properties: ATTENDEE, CONTACT, RELATED-TO
- **FCR-022**: Support date/time properties: COMPLETED, EXDATE, RDATE
- **FCR-023**: Parse VALARM sub-components
- **FCR-024**: Validate VEVENT constraints (e.g., DTEND and DURATION mutual exclusivity)

#### 4.2.3 VTODO Component
- **FCR-025**: Parse required properties: DTSTAMP, UID
- **FCR-026**: Support all VTODO-specific properties: COMPLETED, DUE, PERCENT-COMPLETE
- **FCR-027**: Support STATUS values: NEEDS-ACTION, COMPLETED, IN-PROCESS, CANCELLED
- **FCR-028**: Parse VALARM sub-components

#### 4.2.4 VJOURNAL Component
- **FCR-029**: Parse required properties: DTSTAMP, UID
- **FCR-030**: Support VJOURNAL-specific STATUS values: DRAFT, FINAL, CANCELLED

#### 4.2.5 VFREEBUSY Component
- **FCR-031**: Parse required properties: DTSTAMP, UID
- **FCR-032**: Support required CONTACT and ORGANIZER for published freebusy
- **FCR-033**: Support FREEBUSY property with period values and FBTYPE parameter

#### 4.2.6 VTIMEZONE Component
- **FCR-034**: Parse required TZID property
- **FCR-035**: Support optional LAST-MODIFIED and TZURL
- **FCR-036**: Parse STANDARD and DAYLIGHT sub-components
- **FCR-037**: Parse timezone observance properties: DTSTART, TZOFFSETTO, TZOFFSETFROM
- **FCR-038**: Support optional RRULE, RDATE, TZNAME in observances
- **FCR-039**: Resolve timezone-aware datetime values to UTC or local time

#### 4.2.7 VALARM Component
- **FCR-040**: Parse required ACTION property (AUDIO, DISPLAY, EMAIL)
- **FCR-041**: Support TRIGGER property (absolute or relative duration)
- **FCR-042**: Support REPEAT and DURATION for repeated alarms
- **FCR-043**: Support ACTION=DISPLAY: DESCRIPTION required
- **FCR-044**: Support ACTION=EMAIL: SUMMARY, DESCRIPTION, ATTENDEE required
- **FCR-045**: Support ACTION=AUDIO: ATTACH optional for custom sound

### 4.3 Recurrence Rule (RRULE) Requirements
- **FCR-046**: Parse FREQ component (SECONDLY, MINUTELY, HOURLY, DAILY, WEEKLY, MONTHLY, YEARLY)
- **FCR-047**: Support INTERVAL modifier
- **FCR-048**: Support UNTIL and COUNT termination conditions
- **FCR-049**: Support BYSECOND, BYMINUTE, BYHOUR, BYDAY, BYMONTHDAY, BYYEARDAY, BYWEEKNO, BYMONTH, BYSETPOS modifiers
- **FCR-050**: Support WKST (week start) parameter
- **FCR-051**: Generate recurrence instances from RRULE patterns
- **FCR-052**: Handle timezone-aware recurrence generation
- **FCR-053**: Support EXDATE and RDATE for exceptions and additions

### 4.4 Writing Requirements

#### 4.4.1 Content Line Generation
- **FCR-054**: Generate content lines with CRLF line endings
- **FCR-055**: Fold lines longer than 75 octets per RFC 5545
- **FCR-056**: Never fold within UTF-8 multi-byte sequences
- **FCR-057**: Fold at logical boundaries where possible

#### 4.4.2 Property Value Serialization
- **FCR-058**: Serialize all data types according to RFC 5545 formats
- **FCR-059**: Escape special characters in TEXT values (backslash, semicolon, comma, newline)
- **FCR-060**: Quote parameter values containing COLON, SEMICOLON, or COMMA
- **FCR-061**: Apply RFC 6868 encoding for parameter values containing DQUOTE, newline, or caret
- **FCR-062**: Base64 encode BINARY values with proper line wrapping
- **FCR-063**: Format DATE-TIME values in UTC with Z suffix or with TZID parameter

#### 4.4.3 Component Serialization
- **FCR-064**: Generate valid VCALENDAR wrapper with PRODID and VERSION
- **FCR-065**: Serialize all component types with proper BEGIN/END markers
- **FCR-066**: Maintain property order for readability (group by category)
- **FCR-067**: Include required properties for each component type

---

## 5. Non-Functional Requirements

### 5.1 Performance Requirements
- **NFR-001**: Parse iCalendar files up to 10MB within 2 seconds
- **NFR-002**: Handle files with up to 10,000 events efficiently
- **NFR-003**: Support streaming parsing for files >10MB (memory efficient)
- **NFR-004**: Generation of output should be streaming-capable

### 5.2 Quality Requirements
- **NFR-005**: 100% unit test coverage for all data type parsers
- **NFR-006**: 100% unit test coverage for all component serializers
- **NFR-007**: Pass all iCalendar conformance tests from RFC 5545 examples
- **NFR-008**: Pass validation against iCalendar.org validator test suite
- **NFR-009**: Handle malformed input gracefully with detailed error messages

### 5.3 Security Requirements
- **NFR-010**: Prevent XXE attacks when parsing XML within ATTACH properties
- **NFR-011**: Limit recursion depth to prevent stack overflow attacks
- **NFR-012**: Validate URIs before processing to prevent SSRF
- **NFR-013**: Sanitize all text output to prevent injection attacks

### 5.4 Compatibility Requirements
- **NFR-014**: Support PHP 8.1+ (current stable)
- **NFR-015**: Follow PSR-4 autoloading standards
- **NFR-016**: Follow PSR-12 coding standards
- **NFR-017**: No external dependencies beyond PHP core extensions

---

## 6. Architecture and Design

### 6.1 Class Structure

```
Icalendar/
├── Parser/
│   ├── Parser.php                    # Main parser interface
│   ├── Lexer.php                     # Tokenizes content lines
│   ├── ContentLine.php               # Represents a single content line
│   ├── PropertyParser.php            # Parses property name, params, value
│   ├── ParameterParser.php           # Parses property parameters
│   └── ValueParser/                  # Value type parsers
│       ├── ValueParserInterface.php
│       ├── BinaryParser.php
│       ├── BooleanParser.php
│       ├── CalAddressParser.php
│       ├── DateParser.php
│       ├── DateTimeParser.php
│       ├── DurationParser.php
│       ├── FloatParser.php
│       ├── IntegerParser.php
│       ├── PeriodParser.php
│       ├── RecurParser.php
│       ├── TextParser.php
│       ├── TimeParser.php
│       ├── UriParser.php
│       └── UtcOffsetParser.php
├── Writer/
│   ├── Writer.php                    # Main writer interface
│   ├── ContentLineWriter.php         # Writes content lines with folding
│   ├── PropertyWriter.php            # Serializes properties
│   └── ValueWriter/                  # Value type writers
│       └── (mirrors ValueParser structure)
├── Component/
│   ├── ComponentInterface.php
│   ├── AbstractComponent.php
│   ├── VCalendar.php
│   ├── VEvent.php
│   ├── VTodo.php
│   ├── VJournal.php
│   ├── VFreeBusy.php
│   ├── VTimezone.php
│   ├── VAlarm.php
│   ├── Standard.php
│   └── Daylight.php
├── Property/
│   ├── PropertyInterface.php
│   ├── AbstractProperty.php
│   └── [All property classes by name]
├── Value/
│   ├── ValueInterface.php
│   ├── AbstractValue.php
│   └── [All value type classes]
├── Recurrence/
│   ├── RRule.php                     # Recurrence rule object
│   ├── RRuleParser.php
│   └── RecurrenceGenerator.php       # Generate instances
├── Timezone/
│   ├── TimezoneResolver.php
│   └── TimezoneDatabase.php
├── Validation/
│   ├── Validator.php
│   ├── ValidationError.php
│   └── Rules/
│       └── [Component validation rules]
└── Exception/
    ├── ParseException.php
    ├── ValidationException.php
    └── InvalidDataException.php
```

### 6.2 Key Interfaces

#### Parser Interface
```php
interface ParserInterface
{
    /**
     * Parse iCalendar data string
     * @throws ParseException on invalid data
     */
    public function parse(string $data): VCalendar;
    
    /**
     * Parse iCalendar data from file
     * @throws ParseException on invalid data or file error
     */
    public function parseFile(string $filepath): VCalendar;
    
    /**
     * Enable/disable strict mode
     */
    public function setStrict(bool $strict): void;
}
```

#### Writer Interface
```php
interface WriterInterface
{
    /**
     * Write VCalendar to string
     */
    public function write(VCalendar $calendar): string;
    
    /**
     * Write VCalendar to file
     */
    public function writeToFile(VCalendar $calendar, string $filepath): void;
    
    /**
     * Set line folding behavior
     */
    public function setLineFolding(bool $fold, int $maxLength = 75): void;
}
```

---

## 7. Test Requirements

### 7.1 Unit Test Coverage

#### Parser Tests
| Test Suite | Coverage Area | Minimum Test Cases |
|------------|---------------|-------------------|
| ContentLineTest | Folding/unfolding, line breaks, UTF-8 | 20+ |
| PropertyParserTest | All property syntax variations | 50+ |
| ParameterParserTest | All parameter types, quoting, encoding | 30+ |
| ValueParserTests | Each data type (per type) | 15+ each |
| ComponentParserTest | Each component type | 20+ each |
| RRuleParserTest | All recurrence patterns | 40+ |

#### Writer Tests
| Test Suite | Coverage Area | Minimum Test Cases |
|------------|---------------|-------------------|
| ContentLineWriterTest | Line folding, escaping | 20+ |
| PropertyWriterTest | Property serialization | 40+ |
| ValueWriterTests | Each data type | 10+ each |
| ComponentWriterTest | Component serialization | 15+ each |

#### Integration Tests
| Test Suite | Coverage Area | Test Cases |
|------------|---------------|------------|
| RoundTripTest | Parse → Write → Parse consistency | 50+ |
| ConformanceTest | RFC 5545 example files | All examples |
| EdgeCaseTest | Malformed input handling | 30+ |
| PerformanceTest | Large file handling | 10+ |

### 7.2 Conformance Test Suite

Create test files for all RFC 5545 examples:
- Example: Simple event
- Example: Daily recurring event
- Example: Weekly recurring event with exceptions
- Example: Monthly recurring event
- Example: Yearly recurring event
- Example: All-day event
- Example: To-do with due date
- Example: Journal entry
- Example: Free/busy time
- Example: Timezone with daylight savings
- Example: Alarm (display)
- Example: Alarm (email)
- Example: Alarm (audio)
- Example: Complex meeting with attendees

### 7.3 Test Data Requirements

#### Edge Cases
- Empty property values
- Maximum length content lines
- Maximum nested component depth
- Unicode in various scripts (Latin, Cyrillic, CJK, Arabic, Emoji)
- Malformed folding (fold within UTF-8 sequence)
- Invalid date/time values
- Invalid recurrence rules
- Missing required properties
- Duplicate properties (where not allowed)

#### Stress Tests
- Calendar with 10,000 events
- Calendar with 100 attendees per event
- Calendar with complex nested timezones
- Deeply nested VALARM components
- Very long text descriptions (64KB+)

---

## 8. Error Handling

### 8.1 Error Categories

#### Parse Errors (Recoverable)
- Unknown properties (in non-strict mode)
- Unknown parameters (in non-strict mode)
- Invalid property values (skip property, log warning)

#### Parse Errors (Non-Recoverable)
- Missing required properties (DTSTAMP, UID, PRODID, VERSION)
- Malformed content lines
- Unbalanced component BEGIN/END markers
- Invalid character encoding

#### Validation Errors
- Constraint violations (mutually exclusive properties)
- Invalid date ranges
- Invalid timezone references
- Invalid recurrence rule combinations

### 8.2 Error Reporting

```php
class ValidationError
{
    public function __construct(
        public readonly string $message,
        public readonly string $component,
        public readonly ?string $property,
        public readonly ?string $line,
        public readonly int $lineNumber,
        public readonly ErrorSeverity $severity // WARNING, ERROR, FATAL
    ) {}
}
```

---

## 9. API Examples

### 9.1 Basic Parsing
```php
use Icalendar\Parser\Parser;

$parser = new Parser();
$calendar = $parser->parse($icalData);

// Access events
foreach ($calendar->getComponents('VEVENT') as $event) {
    echo $event->getProperty('SUMMARY')->getValue();
    echo $event->getProperty('DTSTART')->getValue()->format('Y-m-d H:i:s');
}
```

### 9.2 Basic Writing
```php
use Icalendar\Writer\Writer;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;

$calendar = new VCalendar();
$calendar->setProductId('-//MyApp//MyCalendar//EN');
$calendar->setVersion('2.0');

$event = new VEvent();
$event->setUid('event-123@example.com');
$event->setSummary('Team Meeting');
$event->setStart(new DateTime('2026-02-10 10:00:00', $tz));
$event->setEnd(new DateTime('2026-02-10 11:00:00', $tz));

$calendar->addComponent($event);

$writer = new Writer();
$output = $writer->write($calendar);
```

### 9.3 Validation
```php
use Icalendar\Validation\Validator;

$validator = new Validator();
$errors = $validator->validate($calendar);

foreach ($errors as $error) {
    echo "[{$error->severity->name}] Line {$error->lineNumber}: {$error->message}\n";
}
```

### 9.4 Recurrence Generation
```php
use Icalendar\Recurrence\RecurrenceGenerator;

$rrule = $event->getRRule();
$generator = new RecurrenceGenerator();
$instances = $generator->generate($rrule, $startDate, $endDate);

foreach ($instances as $instance) {
    echo $instance->format('Y-m-d H:i:s') . "\n";
}
```

---

## 10. Deliverables

### 10.1 Phase 1 Deliverables
1. Core parser with all RFC 5545 data types
2. All component classes (VEVENT, VTODO, VJOURNAL, VFREEBUSY, VTIMEZONE, VALARM)
3. Writer with folding support
4. Comprehensive unit test suite (500+ tests)
5. Conformance test files
6. API documentation
7. Usage guide

### 10.2 Phase 2 Deliverables
1. RFC 5546 iTIP method support
2. RFC 7529 RSCALE support
3. RFC 7953 VAVAILABILITY component
4. RFC 7986 extended properties
5. Performance optimizations
6. Streaming parser for large files

---

## 11. Acceptance Criteria

### 11.1 Functional Criteria
- [ ] All RFC 5545 example files parse and write correctly
- [ ] All RFC 5545 data types parse and serialize correctly
- [ ] All component types support required and optional properties
- [ ] Recurrence rules generate correct instances
- [ ] Timezone resolution works correctly
- [ ] Validation catches all constraint violations

### 11.2 Quality Criteria
- [ ] 100% unit test coverage for parsers and writers
- [ ] All tests passing
- [ ] PHPStan level 9 compliance
- [ ] Psalm type coverage 100%
- [ ] No memory leaks in long-running processes
- [ ] Handles 10MB files within 2 seconds

### 11.3 Documentation Criteria
- [ ] Complete PHPDoc for all public APIs
- [ ] README with quick start guide
- [ ] Advanced usage documentation
- [ ] Migration guide from other libraries
- [ ] Contributing guidelines

---

## 12. Glossary

| Term | Definition |
|------|------------|
| **iCalendar** | Internet Calendaring and Scheduling Core Object Specification (RFC 5545) |
| **iTIP** | iCalendar Transport-Independent Interoperability Protocol (RFC 5546) |
| **VCALENDAR** | Root component containing all calendar data |
| **VEVENT** | Event component representing a calendar event |
| **VTODO** | To-do component representing a task |
| **VJOURNAL** | Journal component representing a journal entry |
| **VFREEBUSY** | Free/busy component representing availability |
| **VTIMEZONE** | Timezone component with offset rules |
| **VALARM** | Alarm component for reminders |
| **RRULE** | Recurrence rule defining repeating patterns |
| **Content Line** | Single line in iCalendar format (name: value) |
| **Folding** | Splitting long lines per RFC 5545 |
| **Property** | Named attribute with optional parameters and value |
| **Parameter** | Meta-information about a property |
| **Component** | Structured container with properties and sub-components |

---

## 13. References

### Normative References
1. RFC 5545 - Internet Calendaring and Scheduling Core Object Specification (iCalendar)
2. RFC 6868 - Parameter Value Encoding in iCalendar and vCard

### Informative References
3. RFC 5546 - iCalendar Transport-Independent Interoperability Protocol (iTIP)
4. RFC 7529 - Non-Gregorian Recurrence Rules in iCalendar
5. RFC 7953 - Calendar Availability (VAVAILABILITY)
6. RFC 7986 - New Properties for iCalendar
7. RFC 9073 - iCalendar Extended Properties and Relationships
8. RFC 9074 - iCalendar: Event Publishing Extensions

---

## 14. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2026-02-05 | TBD | Initial PRD |

---

**End of Document**
