# Product Requirements Document (PRD)
# PHP iCalendar Parser and Writer Library

**Version:** 1.0.0  
**Date:** 2026-02-05  
**Status:** Draft for Review  

---

## 0. AI Agent Instructions (READ FIRST)

### Document Structure
- **Requirements:** Tagged with `[PRIORITY:HIGH|MEDIUM|LOW]` and `[DEP:ID]` for dependencies
- **Implementation Hints:** Marked with `💡 HINT:` for technical guidance
- **Error Codes:** All errors prefixed with `ICAL-` for programmatic handling
- **Decision Records:** Architectural decisions marked with `📋 ADR-XXX`

### Success Criteria
Before marking complete, verify:
1. All `[PRIORITY:HIGH]` requirements implemented
2. All `ICAL-` error codes implemented and tested
3. All ADRs reflected in code architecture
4. Unit tests cover all error code paths

### Common Patterns
- Data type validation → Throw `InvalidDataException` with appropriate `ICAL-` code
- Parser errors → Throw `ParseException` with line number context
- Validation errors → Return `ValidationError` collection (non-blocking)

---

## 1. Executive Summary

**Objective:** Build RFC 5545 compliant PHP library for iCalendar parsing/writing.

**Scope:**
- Parse and write all RFC 5545 components and data types
- Support VEVENT, VTODO, VJOURNAL, VFREEBUSY, VTIMEZONE, VALARM
- Validate conformance with detailed error reporting
- Zero external dependencies (PHP 8.1+ core only)

**Out of Scope (Phase 2):**
- RFC 5546 iTIP scheduling
- RFC 7529 non-Gregorian recurrence (RSCALE)
- RFC 7953 VAVAILABILITY component
- RFC 7986 extended properties

---

## 2. RFC Compliance Matrix

### Phase 1 (Required)

| RFC | Status | Notes |
|-----|--------|-------|
| RFC 5545 | REQUIRED | Core iCalendar specification - FULL compliance |
| RFC 6868 | REQUIRED | Parameter value encoding (^n, ^^, ^') |

### Phase 2 (Future)

| RFC | Status | Notes |
|-----|--------|-------|
| RFC 5546 | PLANNED | iTIP scheduling methods |
| RFC 7529 | PLANNED | RSCALE non-Gregorian recurrence |
| RFC 7953 | PLANNED | VAVAILABILITY component |
| RFC 7986 | PLANNED | COLOR, IMAGE, CONFERENCE properties |
| RFC 9073 | PLANNED | Structured data |
| RFC 9074 | PLANNED | Event publishing extensions |

---

## 3. Requirements

### 3.1 Core Parsing Requirements

#### 3.1.1 Content Line Processing

| ID | Requirement | Priority | Dependencies | Error Code |
|----|-------------|----------|--------------|------------|
| FCR-001 | Parse CRLF-delimited content lines | HIGH | - | ICAL-PARSE-001 |
| FCR-002 | Unfold lines (CRLF + space/tab) | HIGH | FCR-001 | ICAL-PARSE-002 |
| FCR-003 | Enforce 75-octet line length limit (RFC 5545 §3.1) | HIGH | FCR-001 | ICAL-PARSE-003 |
| FCR-004 | Handle UTF-8 multi-byte sequences when folding | HIGH | FCR-002 | ICAL-PARSE-004 |
| FCR-005 | Preserve binary content byte sequences | MEDIUM | - | ICAL-PARSE-005 |

💡 **HINT:** Store raw bytes internally; decode to UTF-8 only for text properties. Use `mb_strlen($str, '8bit')` for octet counting.

#### 3.1.2 Property Parsing

| ID | Requirement | Priority | Dependencies | Error Code |
|----|-------------|----------|--------------|------------|
| FCR-006 | Parse `name *(";" param) ":" value` format | HIGH | FCR-001 | ICAL-PARSE-006 |
| FCR-007 | Support IANA tokens and X-names for property names | HIGH | FCR-006 | ICAL-PARSE-007 |
| FCR-008 | Parse parameters: `param-name "=" param-value *("," param-value)` | HIGH | FCR-006 | ICAL-PARSE-008 |
| FCR-009 | Handle quoted-string for values containing COLON, SEMICOLON, COMMA | HIGH | FCR-008 | ICAL-PARSE-009 |
| FCR-010 | Decode RFC 6868 sequences (^n→newline, ^^→^, ^'→") | HIGH | FCR-008 | ICAL-PARSE-010 |
| FCR-011 | Support multi-valued comma-separated parameters | MEDIUM | FCR-008 | ICAL-PARSE-011 |
| FCR-012 | Parse values according to declared VALUE type | HIGH | FCR-006 | ICAL-PARSE-012 |

💡 **HINT:** Parameter parsing regex: `/^([^=]+)=(.+)$/`. Handle quoting before splitting on comma.

#### 3.1.3 Data Type Parsing

| Type | Format | Priority | Error Code | Notes |
|------|--------|----------|------------|-------|
| BINARY | Base64 | MEDIUM | ICAL-TYPE-001 | Line wrapping handled |
| BOOLEAN | TRUE/FALSE | MEDIUM | ICAL-TYPE-002 | Case-insensitive |
| CAL-ADDRESS | mailto: URI | MEDIUM | ICAL-TYPE-003 | Validate mailto: scheme |
| DATE | YYYYMMDD | HIGH | ICAL-TYPE-004 | PHP DateTimeImmutable |
| DATE-TIME | YYYYMMDDTHHMMSS[Z] | HIGH | ICAL-TYPE-005 | With/without TZID |
| DURATION | P[n]W or P[n]DT[n]H[n]M[n]S | HIGH | ICAL-TYPE-006 | DateInterval. Note: Use ICAL-TYPE-006 consistently (not ICAL-TYPE-020) |
| FLOAT | Decimal | LOW | ICAL-TYPE-007 | - |
| INTEGER | Signed int | LOW | ICAL-TYPE-008 | - |
| PERIOD | start/end or start/duration | MEDIUM | ICAL-TYPE-009 | - |
| RECUR | RRULE pattern | HIGH | ICAL-TYPE-010 | Complex - see §3.4 |
| TEXT | Escaped text | HIGH | ICAL-TYPE-011 | \\, \;, \,, \n, \N |
| TIME | HHMMSS[Z] | MEDIUM | ICAL-TYPE-012 | Local or UTC |
| URI | Any URI | MEDIUM | ICAL-TYPE-013 | Use filter_var |
| UTC-OFFSET | [+/-]HHMM[SS] | MEDIUM | ICAL-TYPE-014 | - |

💡 **HINT:** Create `ValueParserInterface` with `parse(string $value, ?string $type = null): ValueInterface`. Use factory pattern for type dispatch.

### 3.2 Component Support Requirements

#### VCALENDAR (Root)

| ID | Property | Cardinality | Priority | Error Code |
|----|----------|-------------|----------|------------|
| FCR-014 | PRODID | REQUIRED (1) | HIGH | ICAL-COMP-001 |
| FCR-015 | VERSION | REQUIRED (1) | HIGH | ICAL-COMP-002 |
| FCR-016 | CALSCALE | OPTIONAL (0-1) | MEDIUM | ICAL-COMP-003 |
| FCR-017 | METHOD | OPTIONAL (0-1) | MEDIUM | ICAL-COMP-004 |
| FCR-018 | X-... | OPTIONAL (0+) | LOW | ICAL-COMP-005 |

#### VEVENT

**Required Properties:**
- `DTSTAMP` - Creation timestamp (HIGH - ICAL-VEVENT-001)
- `UID` - Unique identifier (HIGH - ICAL-VEVENT-002)

**Recommended Properties:**
- `DTSTART`, `CLASS`, `CREATED`, `DESCRIPTION`, `GEO`, `LAST-MODIFIED`, `LOCATION`, `ORGANIZER`, `PRIORITY`, `SEQUENCE`, `STATUS`, `SUMMARY`, `TRANSP`, `URL`, `RECURRENCE-ID`, `RRULE`, `DTEND`, `DURATION` (MEDIUM)

**Validation Rules:**

| Rule | Constraint | Error Code |
|------|------------|------------|
| ICAL-VEVENT-VAL-001 | DTEND and DURATION are mutually exclusive | ICAL-VEVENT-VAL-001 |
| ICAL-VEVENT-VAL-002 | If DTSTART has DATE value, DTEND must have DATE value | ICAL-VEVENT-VAL-002 |
| ICAL-VEVENT-VAL-003 | STATUS must be one of: TENTATIVE, CONFIRMED, CANCELLED | ICAL-VEVENT-VAL-003 |

#### VTODO

**Required Properties:**
- `DTSTAMP`, `UID` (HIGH)

**Specific Properties:**
- `COMPLETED`, `DUE`, `PERCENT-COMPLETE`

**STATUS Values:** NEEDS-ACTION, COMPLETED, IN-PROCESS, CANCELLED

#### VJOURNAL

**Required Properties:**
- `DTSTAMP`, `UID` (HIGH)

**STATUS Values:** DRAFT, FINAL, CANCELLED

#### VFREEBUSY

**Required Properties:**
- `DTSTAMP`, `UID` (HIGH)
- `CONTACT`, `ORGANIZER` (for published freebusy)

**Specific Properties:**
- `FREEBUSY` with FBTYPE parameter

#### VTIMEZONE

**Required Properties:**
- `TZID` (HIGH - ICAL-TZ-001)

**Sub-components:**
- `STANDARD` and/or `DAYLIGHT` (REQUIRED at least one - ICAL-TZ-002)

**Observance Properties:**
- Required: `DTSTART`, `TZOFFSETTO`, `TZOFFSETFROM`
- Optional: `RRULE`, `RDATE`, `TZNAME`

💡 **HINT:** Store timezone rules as transition table. Use `DateTimeZone` with transitions array for efficient lookup.

#### VALARM

**Required:**
- `ACTION`: AUDIO, DISPLAY, or EMAIL (HIGH - ICAL-ALARM-001)
- `TRIGGER`: absolute or relative duration (HIGH - ICAL-ALARM-002)

**Action-Specific:**

| Action | Required Properties | Error Code |
|--------|---------------------|------------|
| DISPLAY | DESCRIPTION | ICAL-ALARM-003 |
| EMAIL | SUMMARY, DESCRIPTION, ATTENDEE (at least one) | ICAL-ALARM-004 |
| AUDIO | ATTACH (optional) | - |

**Optional:**
- `REPEAT`, `DURATION` for repeated alarms

**Validation Rules:**

| Rule | Constraint | Error Code |
|------|------------|------------|
| ICAL-ALARM-VAL-001 | REPEAT and DURATION must both be present or both absent | ICAL-ALARM-VAL-001 |
| ICAL-ALARM-VAL-002 | ACTION must be one of: AUDIO, DISPLAY, EMAIL | ICAL-ALARM-VAL-002 |
| ICAL-ALARM-VAL-003 | TRIGGER must be valid DURATION or DATE-TIME | ICAL-ALARM-VAL-003 |

💡 **HINT:** Implement action-specific validation in a separate `validateAction()` method. Check action type first, then validate required properties for that action.

### 3.3 Recurrence Rule (RRULE) Requirements

| ID | Component | Priority | Error Code |
|----|-----------|----------|------------|
| FCR-046 | `FREQ` - SECONDLY to YEARLY | HIGH | ICAL-RRULE-001 |
| FCR-047 | `INTERVAL` modifier | MEDIUM | ICAL-RRULE-002 |
| FCR-048 | `UNTIL` or `COUNT` termination | HIGH | ICAL-RRULE-003 |
| FCR-049 | BY* modifiers (SECOND, MINUTE, HOUR, DAY, MONTHDAY, etc.) | HIGH | ICAL-RRULE-004 |
| FCR-050 | `WKST` week start | MEDIUM | ICAL-RRULE-005 |
| FCR-051 | Generate recurrence instances | HIGH | FCR-046 | ICAL-RRULE-006 |
| FCR-052 | Timezone-aware generation | HIGH | FCR-051 | ICAL-RRULE-007 |
| FCR-053 | EXDATE and RDATE support | HIGH | FCR-051 | ICAL-RRULE-008 |

#### BY* Modifier Details

| Modifier | Values | Example | Notes |
|----------|--------|---------|-------|
| BYSECOND | 0-60 | `BYSECOND=0,30` | 60 for leap second |
| BYMINUTE | 0-59 | `BYMINUTE=0,15,30,45` | |
| BYHOUR | 0-23 | `BYHOUR=9,17` | |
| BYDAY | SU,MO,TU,WE,TH,FR,SA with optional +/-n prefix | `BYDAY=MO,WE,FR` or `BYDAY=2TU` (2nd Tuesday) | |
| BYMONTHDAY | 1-31 or -31 to -1 | `BYMONTHDAY=1,-1` (first and last) | Negative counts from end |
| BYYEARDAY | 1-366 or -366 to -1 | `BYYEARDAY=1,100,200` | |
| BYWEEKNO | 1-53 or -53 to -1 | `BYWEEKNO=1,26,52` | ISO 8601 week numbers |
| BYMONTH | 1-12 | `BYMONTH=1,6,12` | |
| BYSETPOS | 1-366 or -366 to -1 | `BYSETPOS=-1` (last occurrence) | Filters other BY* results |

#### RRULE Test Vectors (Required)

```
# Daily for 10 days
RRULE:FREQ=DAILY;COUNT=10
DTSTART:20260101T090000Z
→ 20260101, 20260102, ..., 20260110 (10 instances)

# Weekly on Tuesday and Thursday
RRULE:FREQ=WEEKLY;BYDAY=TU,TH;UNTIL=20260131T235959Z
DTSTART:20260101T090000Z
→ Jan 1 (Wed skip), Jan 2 (Thu), Jan 6 (Tue), Jan 8 (Thu), ...

# Monthly on 2nd Tuesday
RRULE:FREQ=MONTHLY;BYDAY=2TU;COUNT=6
DTSTART:20260101T090000Z
→ Jan 13, Feb 10, Mar 10, Apr 14, May 12, Jun 9

# Yearly on last day of February
RRULE:FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=-1;COUNT=4
DTSTART:20260101T090000Z
→ Feb 28 2026, Feb 28 2027, Feb 29 2028 (leap), Feb 28 2029
```

💡 **HINT:** Use RRule library pattern: `RRule::parse('FREQ=DAILY;COUNT=5')`. Cache parsed rules. Instance generation is computationally expensive - implement iterator pattern. Use `\Generator` yield for memory efficiency.

### 3.4 Writing Requirements

#### 3.4.1 Content Line Generation

| ID | Requirement | Priority | Error Code |
|----|-------------|----------|------------|
| FCR-054 | CRLF line endings | HIGH | ICAL-WRITE-001 |
| FCR-055 | Fold at 75 octets | HIGH | ICAL-WRITE-002 |
| FCR-056 | Never fold within UTF-8 sequences | HIGH | ICAL-WRITE-003 |
| FCR-057 | Fold at logical boundaries | MEDIUM | ICAL-WRITE-004 |

#### 3.4.2 Property Serialization

| ID | Requirement | Priority | Error Code |
|----|-------------|----------|------------|
| FCR-058 | Serialize per RFC 5545 formats | HIGH | ICAL-WRITE-005 |
| FCR-059 | Escape TEXT values (\\, \;, \,, \n, \N) | HIGH | ICAL-WRITE-006 |
| FCR-060 | Quote params with COLON, SEMICOLON, COMMA | HIGH | ICAL-WRITE-007 |
| FCR-061 | RFC 6868 encode (DQUOTE, newline, caret) | HIGH | ICAL-WRITE-008 |
| FCR-062 | Base64 BINARY with line wrapping | MEDIUM | ICAL-WRITE-009 |
| FCR-063 | DATE-TIME UTC with Z or TZID param | HIGH | ICAL-WRITE-010 |

#### 3.4.3 Component Serialization

| ID | Requirement | Priority | Error Code |
|----|-------------|----------|------------|
| FCR-064 | VCALENDAR with PRODID, VERSION | HIGH | ICAL-WRITE-011 |
| FCR-065 | Proper BEGIN/END markers | HIGH | ICAL-WRITE-012 |
| FCR-066 | Group properties by category | LOW | - |
| FCR-067 | Include required properties | HIGH | ICAL-WRITE-013 |

---

## 4. Non-Functional Requirements

### 4.1 Performance

| ID | Requirement | Target | Priority |
|----|-------------|--------|----------|
| NFR-001 | Parse 10MB file | < 2 seconds | MEDIUM |
| NFR-002 | Handle 10,000 events | Memory < 128MB | MEDIUM |
| NFR-003 | Streaming for files > 10MB | Iterator pattern | MEDIUM |
| NFR-004 | Streaming output | Generator | LOW |

📋 **ADR-001:** Use generator pattern for large file parsing to maintain constant memory usage regardless of file size.

### 4.2 Quality

| ID | Requirement | Target | Priority |
|----|-------------|--------|----------|
| NFR-005 | Unit test coverage - data type parsers | 100% | HIGH |
| NFR-006 | Unit test coverage - component serializers | 100% | HIGH |
| NFR-007 | RFC 5545 conformance tests | Pass all | HIGH |
| NFR-008 | iCalendar.org validation | Pass | HIGH |
| NFR-009 | Malformed input handling | Graceful degradation | HIGH |

### 4.3 Security

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-010 | Prevent XXE in ATTACH properties | HIGH |
| NFR-011 | Limit recursion depth (default: 100) | HIGH |
| NFR-012 | Validate URIs to prevent SSRF | MEDIUM |
| NFR-013 | Sanitize text output | MEDIUM |

#### Security Implementation Details

**NFR-010 (XXE Prevention):**
- ATTACH properties with VALUE=URI must validate scheme (only http, https, file with restrictions)
- ATTACH with ENCODING=BASE64 is safe (no external reference)
- Block data: URIs over 1MB to prevent memory exhaustion
- Implementation: `AttachValidator::validateUri(string $uri): bool`

**NFR-011 (Recursion Depth):**
- Track component nesting depth during parsing
- Default limit: 100 levels
- Configurable via `Parser::setMaxDepth(int $depth)`
- Implementation: Pass depth counter through parse methods

**NFR-012 (SSRF Prevention):**
- Validate URI schemes in URL, ATTACH, IMAGE properties
- Allowed schemes: http, https, mailto, tel, urn
- Block: file://, ftp://, gopher://, data: (except for small inline data)
- Block private IP ranges in URLs: 10.x, 172.16-31.x, 192.168.x, 127.x, ::1
- Implementation: `UriValidator::validateForSsrf(string $uri): bool`

**NFR-013 (Text Sanitization):**
- Escape control characters in output (except \n)
- Strip null bytes from all text values
- Implementation: `TextWriter::sanitize(string $text): string`

💡 **HINT:** For NFR-011, track depth in parser state: `if ($depth > $maxDepth) throw new ParseException('ICAL-SEC-001')`. Create a SecurityValidator class that consolidates all security checks.

### 4.4 Compatibility

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-014 | PHP 8.1+ support | HIGH |
| NFR-015 | PSR-4 autoloading | HIGH |
| NFR-016 | PSR-12 coding standards | MEDIUM |
| NFR-017 | Zero external dependencies | HIGH |

📋 **ADR-002:** No external dependencies policy ensures library works in restricted hosting environments and reduces supply chain attack surface.

---

## 5. Architecture

### 5.1 Directory Structure

```
src/
├── Parser/
│   ├── Parser.php                    # Main parser interface
│   ├── Lexer.php                     # Tokenizes content lines
│   ├── ContentLine.php               # Single content line
│   ├── PropertyParser.php            # Parses property name/params/value
│   ├── ParameterParser.php           # Parses parameters
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
│   ├── ContentLineWriter.php         # Line folding
│   ├── PropertyWriter.php            # Property serialization
│   └── ValueWriter/                  # Mirrors ValueParser
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
│   └── [Property classes by name]
├── Value/
│   ├── ValueInterface.php
│   ├── AbstractValue.php
│   └── [Value type classes]
├── Recurrence/
│   ├── RRule.php
│   ├── RRuleParser.php
│   └── RecurrenceGenerator.php
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

### 5.2 Lexer Design

The Lexer tokenizes raw iCalendar data into content lines. It handles:

1. **Line ending normalization** - Convert LF, CR, CRLF to CRLF
2. **Line unfolding** - Join continuation lines (CRLF + space/tab)
3. **Token generation** - Yield ContentLine objects via generator

```php
class Lexer
{
    /**
     * Tokenize iCalendar data into content lines
     * @return \Generator<ContentLine>
     */
    public function tokenize(string $data): \Generator;

    /**
     * Tokenize from file with streaming (constant memory)
     * @return \Generator<ContentLine>
     */
    public function tokenizeFile(string $filepath): \Generator;
}
```

💡 **HINT:** Use `\Generator` to yield lines one at a time. Track line numbers for error reporting. Buffer incomplete lines when reading file chunks.

### 5.3 Key Interfaces

#### ParserInterface

```php
interface ParserInterface
{
    /**
     * Parse iCalendar data string
     * @throws ParseException with error code ICAL-PARSE-XXX
     */
    public function parse(string $data): VCalendar;
    
    /**
     * Parse from file
     * @throws ParseException with error code ICAL-PARSE-XXX or ICAL-IO-XXX
     */
    public function parseFile(string $filepath): VCalendar;
    
    /**
     * Set strict mode (throw on unknown props/params)
     */
    public function setStrict(bool $strict): void;
    
    /**
     * Get last parse errors (non-fatal)
     * @return ValidationError[]
     */
    public function getErrors(): array;
}
```

#### WriterInterface

```php
interface WriterInterface
{
    /**
     * Write VCalendar to string
     * @throws InvalidDataException with error code ICAL-WRITE-XXX
     */
    public function write(VCalendar $calendar): string;
    
    /**
     * Write to file
     * @throws \RuntimeException with error code ICAL-IO-001
     */
    public function writeToFile(VCalendar $calendar, string $filepath): void;
    
    /**
     * Configure line folding
     */
    public function setLineFolding(bool $fold, int $maxLength = 75): void;
}
```

### 5.3 Error Classes

#### ParseException

```php
class ParseException extends \Exception
{
    public function __construct(
        string $message,
        string $code,           // ICAL-PARSE-XXX, ICAL-TYPE-XXX
        int $lineNumber = 0,
        ?string $line = null,
        ?\Throwable $previous = null
    );
    
    public function getErrorCode(): string;
    public function getLineNumber(): int;
    public function getLine(): ?string;
}
```

#### ValidationError

```php
class ValidationError
{
    public function __construct(
        public readonly string $code,           // ICAL-XXX-YYY
        public readonly string $message,
        public readonly string $component,
        public readonly ?string $property,
        public readonly ?string $line,
        public readonly int $lineNumber,
        public readonly ErrorSeverity $severity // WARNING, ERROR, FATAL
    );
}
```

---

## 6. Error Code Reference

### Parser Errors (ICAL-PARSE-XXX)

| Code | Description | Recoverable |
|------|-------------|-------------|
| ICAL-PARSE-001 | Invalid line ending (not CRLF) | Yes (fix) |
| ICAL-PARSE-002 | Malformed folding | Yes (fix) |
| ICAL-PARSE-003 | Line exceeds 75 octets | Yes (warn) |
| ICAL-PARSE-004 | UTF-8 sequence broken by folding | Yes (fix) |
| ICAL-PARSE-005 | Binary data corruption | No |
| ICAL-PARSE-006 | Invalid property format | No |
| ICAL-PARSE-007 | Invalid property name | Yes (skip) |
| ICAL-PARSE-008 | Invalid parameter format | No |
| ICAL-PARSE-009 | Unclosed quoted string | No |
| ICAL-PARSE-010 | Invalid RFC 6868 encoding | Yes (fix) |
| ICAL-PARSE-011 | Invalid multi-value parameter | Yes (skip value) |
| ICAL-PARSE-012 | Type declaration mismatch | Yes (warn) |

### Data Type Errors (ICAL-TYPE-XXX)

| Code | Type | Error Condition |
|------|------|-----------------|
| ICAL-TYPE-001 | BINARY | Invalid Base64 |
| ICAL-TYPE-002 | BOOLEAN | Not TRUE/FALSE |
| ICAL-TYPE-003 | CAL-ADDRESS | Invalid mailto: URI |
| ICAL-TYPE-004 | DATE | Not YYYYMMDD format |
| ICAL-TYPE-005 | DATE-TIME | Invalid format or date |
| ICAL-TYPE-006 | DURATION | Invalid ISO 8601 duration |
| ICAL-TYPE-007 | FLOAT | Not a valid decimal |
| ICAL-TYPE-008 | INTEGER | Not a valid integer |
| ICAL-TYPE-009 | PERIOD | Invalid period format |
| ICAL-TYPE-010 | RECUR | Invalid RRULE syntax |
| ICAL-TYPE-011 | TEXT | Invalid escape sequence |
| ICAL-TYPE-012 | TIME | Invalid HHMMSS format |
| ICAL-TYPE-013 | URI | Invalid URI format |
| ICAL-TYPE-014 | UTC-OFFSET | Invalid offset format |

### Component Errors (ICAL-COMP-XXX, ICAL-[COMPONENT]-XXX)

| Code | Component | Error |
|------|-----------|-------|
| ICAL-COMP-001 | VCALENDAR | Missing PRODID |
| ICAL-COMP-002 | VCALENDAR | Missing VERSION |
| ICAL-VEVENT-001 | VEVENT | Missing DTSTAMP |
| ICAL-VEVENT-002 | VEVENT | Missing UID |
| ICAL-VEVENT-VAL-001 | VEVENT | DTEND and DURATION both present |
| ICAL-TZ-001 | VTIMEZONE | Missing TZID |
| ICAL-TZ-002 | VTIMEZONE | No STANDARD/DAYLIGHT sub-component |
| ICAL-ALARM-001 | VALARM | Missing ACTION |
| ICAL-ALARM-002 | VALARM | Missing TRIGGER |

### Security Errors (ICAL-SEC-XXX)

| Code | Description |
|------|-------------|
| ICAL-SEC-001 | Maximum nesting depth exceeded |
| ICAL-SEC-002 | XXE attack detected in ATTACH |
| ICAL-SEC-003 | SSRF attempt in URI |

### IO Errors (ICAL-IO-XXX)

| Code | Description |
|------|-------------|
| ICAL-IO-001 | File not found or unreadable |
| ICAL-IO-002 | File write error |
| ICAL-IO-003 | Permission denied |

---

## 7. Test Requirements

### 7.1 Test Categories

| Category | Coverage Target | Test Types |
|----------|----------------|------------|
| Unit Tests | 100% parsers/writers | PHPUnit |
| Integration | Parse→Write→Parse roundtrip | PHPUnit |
| Conformance | All RFC 5545 examples | PHPUnit + data files |
| Edge Cases | Malformed input handling | PHPUnit |
| Performance | 10MB/2s, 10K events | PHPUnit + benchmarks |

### 7.2 Required Test Data

#### RFC 5545 Examples (MUST PASS)

Create test cases for all RFC 5545 examples:
1. Simple event
2. Daily recurring event
3. Weekly recurring with exceptions
4. Monthly recurring
5. Yearly recurring
6. All-day event
7. To-do with due date
8. Journal entry
9. Free/busy time
10. Timezone with DST
11. Display alarm
12. Email alarm
13. Audio alarm
14. Complex meeting with attendees

#### Edge Cases

| Category | Test Cases |
|----------|------------|
| Empty values | Empty TEXT, empty DESCRIPTION |
| Line lengths | Exactly 75 octets, 76 octets (must fold) |
| Nesting | Max depth (100), depth+1 (should fail) |
| Unicode | Latin, Cyrillic, CJK, Arabic, Emoji in various properties |
| Malformed | UTF-8 sequence split by fold, invalid dates |
| Duplicates | Duplicate UIDs, duplicate properties |

#### Stress Tests

| Test | Specification |
|------|--------------|
| Large calendar | 10,000 events |
| Many attendees | 100 attendees per event |
| Complex timezones | Nested timezone rules |
| Deep alarms | 5-level nested VALARM |
| Long text | 64KB+ description |

---

## 8. API Examples

### 8.1 Parsing

```php
use Icalendar\Parser\Parser;

$parser = new Parser();
$parser->setStrict(true);

try {
    $calendar = $parser->parse($icalData);
    
    // Access events
    foreach ($calendar->getComponents('VEVENT') as $event) {
        echo $event->getProperty('SUMMARY')->getValue();
        echo $event->getProperty('DTSTART')->getValue()->format('Y-m-d H:i:s');
    }
} catch (ParseException $e) {
    // Handle fatal errors
    echo "Parse error {$e->getErrorCode()} at line {$e->getLineNumber()}: {$e->getMessage()}";
}

// Check non-fatal warnings
foreach ($parser->getErrors() as $error) {
    echo "[{$error->severity->name}] {$error->code}: {$error->message}\n";
}
```

### 8.2 Writing

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

### 8.3 Validation

```php
use Icalendar\Validation\Validator;

$validator = new Validator();
$errors = $validator->validate($calendar);

foreach ($errors as $error) {
    echo "[{$error->code}] Line {$error->lineNumber}: {$error->message}\n";
}

$isValid = count(array_filter($errors, fn($e) => $e->severity === ErrorSeverity::ERROR)) === 0;
```

### 8.4 Recurrence

```php
use Icalendar\Recurrence\RecurrenceGenerator;

$rrule = $event->getRRule();
$generator = new RecurrenceGenerator();

// Get occurrences between dates
$instances = $generator->generate($rrule, $startDate, $endDate);

foreach ($instances as $instance) {
    echo $instance->format('Y-m-d H:i:s') . "\n";
}
```

---

## 9. Acceptance Criteria

### 9.1 Functional (All Must Pass)

- [ ] All RFC 5545 example files parse and serialize correctly
- [ ] All data types parse/serialize per specification
- [ ] All components support required properties
- [ ] Recurrence rules generate correct instances (verified against test vectors)
- [ ] Timezone resolution accurate (test against known transitions)
- [ ] Validation catches all constraint violations
- [ ] All ICAL-XXX error codes implemented and tested

### 9.2 Quality (All Must Pass)

- [ ] Unit test coverage ≥ 100% for parsers and writers (measured by PHPUnit)
- [ ] All tests passing (./vendor/bin/phpunit)
- [ ] PHPStan level 9 (no errors)
- [ ] Psalm type coverage 100%
- [ ] No memory leaks (test with 10K events, memory stable)
- [ ] Performance: 10MB file < 2 seconds

### 9.3 Documentation (All Must Pass)

- [ ] PHPDoc for all public APIs
- [ ] README with quick start
- [ ] Error code reference (this document §6)
- [ ] Architecture decision records (ADRs)

---

## 10. Deliverables

### Phase 1

1. [ ] Core parser with all RFC 5545 data types
2. [ ] All component classes (VEVENT, VTODO, VJOURNAL, VFREEBUSY, VTIMEZONE, VALARM)
3. [ ] Writer with folding support
4. [ ] Comprehensive test suite (unit + integration + conformance)
5. [ ] Conformance test files (RFC 5545 examples)
6. [ ] API documentation (PHPDoc)
7. [ ] Usage guide (README)

### Phase 2

1. [ ] RFC 5546 iTIP method support
2. [ ] RFC 7529 RSCALE support
3. [ ] RFC 7953 VAVAILABILITY component
4. [ ] RFC 7986 extended properties
5. [ ] Performance optimizations
6. [ ] Streaming parser for large files

---

## 11. Glossary

| Term | Definition |
|------|------------|
| **iCalendar** | RFC 5545 standard for calendar data exchange |
| **iTIP** | RFC 5546 scheduling protocol |
| **VCALENDAR** | Root component container |
| **VEVENT** | Calendar event component |
| **VTODO** | Task/to-do component |
| **VJOURNAL** | Journal entry component |
| **VFREEBUSY** | Free/busy availability component |
| **VTIMEZONE** | Timezone definition component |
| **VALARM** | Alarm/reminder component |
| **RRULE** | Recurrence rule pattern |
| **Content Line** | Single name:value line |
| **Folding** | Line splitting per RFC 5545 |
| **Property** | Named attribute with value |
| **Parameter** | Property metadata |
| **Component** | Structured container |
| **RFC 6868** | Caret-encoded parameter values |

---

## 12. References

### Normative

1. RFC 5545 - iCalendar Core Specification
2. RFC 6868 - Parameter Value Encoding

### Informative

3. RFC 5546 - iTIP
4. RFC 7529 - Non-Gregorian Recurrence
5. RFC 7953 - VAVAILABILITY
6. RFC 7986 - Extended Properties
7. RFC 9073 - Structured Data
8. RFC 9074 - Event Publishing

---

## 13. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2026-02-05 | TBD | Initial PRD - AI-optimized |

---

**End of Document**
