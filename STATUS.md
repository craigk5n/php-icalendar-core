# Project Status Tracker
# PHP iCalendar Parser and Writer Library

**Version:** 1.0.0  
**Date:** 2026-02-05  
**Status:** In Progress  

---

## Overview

This document tracks the implementation progress of the PHP iCalendar library. Work is organized into epics with detailed tasks, each containing:
- Full description and importance
- Acceptance criteria (test-focused)
- Dependencies and priority
- Status tracking

---

## Epic 1: Foundation & Core Infrastructure

**Description:** Establish the basic project structure, interfaces, and error handling system that all other components depend on.

**Why Important:** Without proper foundation, the parser/writer components would lack consistency, proper error reporting, and maintainable architecture.

---

### Task 1.1: Project Structure Setup

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 2 hours

**Description:** Create the complete directory structure as defined in PRD §5.1, including all namespaces and placeholder files.

**Why Important:** Establishes the foundation for PSR-4 autoloading and organized code structure.

**Acceptance Criteria:**
- [x] All directories from PRD §5.1 exist
- [x] composer.json with PSR-4 autoloading configured
- [x] All interface files created with proper signatures
- [x] All exception classes created with proper constructors
- [x] `./vendor/bin/phpunit` runs without errors (even with no tests)

**Dependencies:** None

---

### Task 1.2: Exception Classes Implementation

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 4 hours

**Description:** Implement all exception classes (ParseException, ValidationException, InvalidDataException) with proper error codes and context.

**Why Important:** Provides consistent error handling throughout the library with RFC-compliant error codes.

**Acceptance Criteria:**
- [x] ParseException stores line number, line content, and error code
- [x] ValidationError stores component, property, severity, and context
- [x] All ICAL-XXX error codes from PRD §6 are defined as constants
- [x] Exception classes are serializable (for logging)
- [x] Tests verify all properties are accessible via getters

**Dependencies:** Task 1.1

**Test Requirements:**
```php
// Tests pass:
testParseExceptionStoresContext() ✓
testValidationErrorSeverity() ✓
testAllErrorCodesDefined() ✓
testExceptionSerialization() ✓
```

---

### Task 1.3: Core Interfaces

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 6 hours

**Description:** Implement all core interfaces (ParserInterface, WriterInterface, ComponentInterface, PropertyInterface, ValueInterface).

**Why Important:** Defines contracts for all major components, ensuring consistency and testability.

**Acceptance Criteria:**
- [x] ParserInterface matches PRD §5.2 exactly
- [x] WriterInterface matches PRD §5.2 exactly
- [x] All interfaces have complete PHPDoc
- [x] Interfaces are type-hinted with return types
- [x] ErrorSeverity enum implemented (WARNING, ERROR, FATAL)

**Dependencies:** Task 1.2

**Test Requirements:**
```php
// Tests pass:
testParserInterfaceContract() ✓
testWriterInterfaceContract() ✓
testComponentInterfaceContract() ✓
testErrorSeverityEnum() ✓
```

---

## Epic 2: Content Line Processing

**Description:** Implement the low-level content line parsing and folding logic that handles the iCalendar line format.

**Why Important:** Content lines are the fundamental building blocks of iCalendar data. All parsing depends on correctly handling line endings, folding, and UTF-8 sequences.

---

### Task 2.1: Content Line Class

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 4 hours

**Description:** Create ContentLine class that represents a single parsed content line with name, parameters, and value.

**Why Important:** Provides a structured way to handle individual lines before property parsing.

**Acceptance Criteria:**
- [x] ContentLine stores raw line, name, parameters array, and value
- [x] Implements __toString() for debugging
- [x] Provides accessors for all parts
- [x] Handles empty parameter lists correctly
- [x] Validates line format (contains colon)

**Dependencies:** Task 1.3

**Test Requirements:**
```php
// Tests pass:
testContentLineParsesSimpleProperty() ✓
testContentLineParsesWithParameters() ✓
testContentLineHandlesEmptyParameters() ✓
testContentLineToString() ✓
testContentLineValidation() ✓
```

---

### Task 2.2: Line Folding/Unfolding

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 6 hours

**Description:** Implement line unfolding logic that handles CRLF + space/tab continuation according to RFC 5545 §3.1.

**Why Important:** iCalendar files can have long lines that are folded. Incorrect handling breaks parsing.

**Acceptance Criteria:**
- [x] Unfold lines with CRLF + space
- [x] Unfold lines with CRLF + tab
- [x] Preserve exact whitespace in unfolded content
- [x] Handle multiple consecutive folded lines
- [x] Detect malformed folding (no space/tab after CRLF)
- [x] Use mb_strlen() with '8bit' for octet counting

**Dependencies:** Task 2.1

**Test Requirements:**
```php
// Tests pass:
testUnfoldSimpleLine() ✓
testUnfoldMultipleLines() ✓
testUnfoldWithTab() ✓
testUnfoldPreservesWhitespace() ✓
testUnfoldDetectsMalformed() ✓
testUnfoldUtf8Sequences() ✓
```

---

### Task 2.3: Line Folding for Output

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 4 hours

**Description:** Implement line folding for output that never splits UTF-8 sequences and respects 75-octet limit.

**Why Important:** Ensures generated iCalendar files are RFC-compliant and readable.

**Acceptance Criteria:**
- [x] Fold lines longer than 75 octets
- [x] Never fold within UTF-8 multi-byte sequences
- [x] Insert CRLF + space at fold points
- [x] Prefer folding at logical boundaries (after semicolons, commas)
- [x] Handle lines with no logical boundaries correctly

**Dependencies:** Task 2.2

**Test Requirements:**
```php
// Tests pass:
testFoldLongLine() ✓
testFoldUtf8Sequence() ✓
testFoldAtLogicalBoundary() ✓
testFoldNoBoundary() ✓
testFoldExact75Octets() ✓
```

---

## Epic 3: Property Parsing System

**Description:** Implement the property parsing system that handles property names, parameters, and values according to RFC 5545 syntax.

**Why Important:** Properties are the main data carriers in iCalendar. Proper parsing is essential for data integrity.

---

### Task 3.1: Property Parser

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 8 hours

**Description:** Create PropertyParser that parses property name, parameters, and value from content lines.

**Why Important:** Centralizes property parsing logic and handles all RFC 5545 property syntax variations.

**Acceptance Criteria:**
- [x] Parse properties in format: `name *(";" param) ":" value`
- [x] Support IANA tokens and X-names for property names
- [x] Handle properties without parameters
- [x] Handle properties with multiple parameters
- [x] Validate property name format
- [x] Return ContentLine object with parsed data

**Additional Features Implemented:**
- Full RFC 5545 property name validation (IANA tokens and X-names)
- Quoted parameter value support for values containing `:`, `;`, `,`
- RFC 6868 parameter value encoding/decoding (`^n`, `^^`, `^'`)
- Multi-valued comma-separated parameter support
- Unclosed quote detection and validation
- Line number context for error reporting

**Dependencies:** Task 2.1

**Test Requirements:**
```php
// Tests pass:
testParseSimpleProperty() ✓
testParsePropertyWithParameters() ✓
testParseXNameProperty() ✓
testParsePropertyWithoutParameters() ✓
testParseInvalidPropertyFormat() ✓
testParseMultipleParameters() ✓
// Additional tests:
testParseQuotedParameterValue() ✓
testParseRfc6868NewlineEncoding() ✓
testParseMultiValuedParameter() ✓
testParseUnclosedQuotedString() ✓
```

---

### Task 3.2: Parameter Parser

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 6 hours

**Description:** Create ParameterParser that handles parameter name/value pairs with quoting and multi-value support.

**Why Important:** Parameters carry metadata about properties. Incorrect parsing loses important information.

**Acceptance Criteria:**
- [x] Parse parameters: `param-name "=" param-value *("," param-value)`
- [x] Handle quoted-string values containing COLON, SEMICOLON, COMMA
- [x] Support multi-valued comma-separated parameters
- [x] Decode RFC 6868 sequences (^n, ^^, ^')
- [x] Handle unquoted values correctly
- [x] Return associative array of parameters

**Additional Features Implemented:**
- Parameter name validation (IANA tokens only)
- Independent parameter parsing capability
- Integration with PropertyParser via composition
- Comprehensive error messages with line number context

**Dependencies:** Task 3.1

**Test Requirements:**
```php
// Tests pass:
testParseSimpleParameter() ✓
testParseQuotedParameter() ✓
testParseMultiValueParameter() ✓
testParseRfc6868Decoding() ✓
testParseUnquotedValue() ✓
testParseInvalidParameter() ✓
```

---

### Task 3.3: Value Parser Factory

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 4 hours

**Description:** Create ValueParserFactory that dispatches to appropriate value parser based on VALUE parameter or property name.

**Why Important:** Provides extensible system for parsing different data types.

**Acceptance Criteria:**
- [x] Factory returns correct parser for each data type
- [x] Handle VALUE parameter to override default type
- [x] Default to TEXT type when no VALUE parameter
- [x] Support all RFC 5545 data types
- [x] Throw exception for unknown data types
- [x] Cache parser instances for performance

**Implementation Details:**
- Created `ValueParserInterface` defining the contract for all value parsers
- Created `ValueParserFactory` with:
  - Parser caching for performance
  - Case-insensitive type matching
  - Property-based default type mapping
  - VALUE parameter override support
  - Support for 14 RFC 5545 data types
  - Custom parser registration capability
- Updated all 15 value parser classes to implement the interface

**Dependencies:** Task 3.2

**Test Requirements:**
```php
// Tests pass:
testFactoryReturnsDateParser() ✓
testFactoryReturnsDateTimeParser() ✓
testFactoryUsesValueParameter() ✓
testFactoryDefaultsToText() ✓
testFactoryThrowsForUnknownType() ✓
testFactoryCachesParsers() ✓
```

---

## Epic 4: Data Type Parsers

**Description:** Implement parsers for all RFC 5545 data types (BINARY, BOOLEAN, DATE, DATE-TIME, etc.).

**Why Important:** Each data type has specific parsing rules. Incorrect parsing leads to data corruption.

---

### Task 4.1: Date/Time Parsers

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 8 hours

**Description:** Implement DateParser and DateTimeParser for handling DATE and DATE-TIME values.

**Why Important:** Dates and times are the most common data types in calendars. Accuracy is critical.

**Acceptance Criteria:**
- [x] DateParser parses YYYYMMDD format
- [x] DateTimeParser parses YYYYMMDDTHHMMSS[Z] format
- [x] Handle UTC times with Z suffix
- [x] Handle local times without Z
- [x] Handle timezone-aware times with TZID parameter
- [x] Return PHP DateTimeImmutable objects
- [x] Validate date ranges (e.g., no month > 12)

**Implementation Details:**
- **DateParser:** Parses YYYYMMDD format, validates dates including leap years, supports TZID parameter
- **DateTimeParser:** Parses YYYYMMDDTHHMMSS[Z] format, handles UTC (Z suffix), local times, and TZID parameter
- Both parsers validate all components (year, month, day, hour, minute, second)
- Leap year validation follows RFC 5545 rules (century years only leap if divisible by 400)
- Returns DateTimeImmutable objects for immutability

**Dependencies:** Task 3.3

**Test Requirements:**
```php
// Tests pass:
testParseDate() ✓
testParseDateTimeUtc() ✓
testParseDateTimeLocal() ✓
testParseDateTimeWithTzid() ✓
testParseInvalidDate() ✓
testParseInvalidDateTime() ✓
```

---

### Task 4.2: Text Parser

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 4 hours

**Description:** Implement TextParser for handling escaped text values.

**Why Important:** Text is used for descriptions, summaries, and other human-readable content.

**Acceptance Criteria:**
- [x] Unescape \\, \;, \,, \n, \N sequences
- [x] Handle empty text values
- [x] Preserve Unicode characters correctly
- [x] Handle very long text values
- [x] Return string values
- [x] Throw ParseException for invalid escape sequences

**Dependencies:** Task 3.3

**Implementation Details:**
- Created `TextParser` class implementing `ValueParserInterface`
- Parses TEXT values according to RFC 5545 §3.3.1
- Handles escape sequences: `\\` → `\`, `\;` → `;`, `\,` → `,`, `\n` or `\N` → newline
- Validates all escape sequences, throws `ParseException` with error code `ICAL-TYPE-011`
- Supports full Unicode (UTF-8) including CJK, Arabic, emoji
- Handles very long text values efficiently

**Test Requirements:**
```php
// Tests pass:
testUnescapeBackslash() ✓
testUnescapeSemicolon() ✓
testUnescapeComma() ✓
testUnescapeNewline() ✓
testParseEmptyText() ✓
testParseUnicodeText() ✓
testParseUnicodeEmoji() ✓
testParseUnicodeCjk() ✓
testParseUnicodeArabic() ✓
testParseVeryLongText() ✓
testParsePlainText() ✓
testParseMultipleEscapes() ✓
testParseConsecutiveBackslashes() ✓
testParseInvalidEscapeSequence() ✓
testParseTrailingBackslash() ✓
testParseRealWorldDescription() ✓
testGetType() ✓
testCanParseValid() ✓
testCanParseInvalidTrailingBackslash() ✓
testCanParseValidDoubleBackslashAtEnd() ✓
testParseSpecialCharactersPreserved() ✓
testParseMixedContent() ✓
testParseOnlyBackslash() ✓
testParseOnlyNewlineEscape() ✓
testParseEmptyEscape() ✓
testParseWithParameters() ✓
testParseMultilineEscaped() ✓
```

---

### Task 4.3: Duration Parser

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 6 hours

**Description:** Implement DurationParser for ISO 8601 duration strings.

**Why Important:** Durations are used for event lengths and alarm triggers.

**Acceptance Criteria:**
- [x] Parse P[n]W format (weeks)
- [x] Parse P[n]DT[n]H[n]M[n]S format
- [x] Handle negative durations
- [x] Parse durations with missing components
- [x] Return PHP DateInterval objects
- [x] Validate duration format

**Dependencies:** Task 3.3

**Implementation Details:**
- Created `DurationParser` class implementing `ValueParserInterface`
- Parses ISO 8601 duration strings according to RFC 5545 §3.3.6
- Supports weeks (W), days (D), hours (H), minutes (M), seconds (S)
- Handles negative durations with leading minus sign
- Returns PHP DateInterval objects
- Validates all components and throws `ParseException` with error code `ICAL-TYPE-020`

**Test Requirements:**
```php
// Tests pass:
testParseWeekDuration() ✓
testParseDayDuration() ✓
testParseHourDuration() ✓
testParseMinuteDuration() ✓
testParseSecondDuration() ✓
testParseFullDuration() ✓
testParseDurationWithWeeksAndTime() ✓
testParseNegativeWeekDuration() ✓
testParseNegativeDayDuration() ✓
testParseNegativeTimeDuration() ✓
testParseZeroDuration() ✓
testParseEmptyDuration() ✓
testParseMissingP() ✓
testParseInvalidFormat() ✓
testGetType() ✓
testCanParseValidWeekDuration() ✓
testCanParseValidDayDuration() ✓
testCanParseValidTimeDuration() ✓
testCanParseNegativeDuration() ✓
```

---

### Task 4.4: Remaining Data Type Parsers

**Status:** ✅ Completed  
**Priority:** MEDIUM  
**Effort:** 8 hours

**Description:** Implement parsers for BINARY, BOOLEAN, CAL-ADDRESS, FLOAT, INTEGER, PERIOD, RECUR, TIME, URI, UTC-OFFSET.

**Why Important:** Complete data type support ensures full RFC compliance.

**Acceptance Criteria:**
- [x] BINARY: Base64 decode with line unwrapping
- [x] BOOLEAN: Case-insensitive TRUE/FALSE
- [x] CAL-ADDRESS: Validate mailto: URI scheme
- [x] FLOAT/INTEGER: Parse numeric values
- [x] PERIOD: Parse start/end or start/duration
- [x] RECUR: Parse RRULE
- [x] TIME: Parse HHMMSS[Z] format
- [x] URI: Validate with filter_var
- [x] UTC-OFFSET: Parse [+/-]HHMM[SS] format

**Dependencies:** Task 4.3

**Implementation Details:**
- **IntegerParser:** Parses signed integers, validates format
- **FloatParser:** Parses decimal numbers with optional decimal point
- **BooleanParser:** Case-insensitive TRUE/FALSE parsing
- **BinaryParser:** Base64 decoding with line unwrapping for continuation
- **UriParser:** Validates URIs using filter_var
- **CalAddressParser:** Validates mailto: URI scheme with email address
- **TimeParser:** Parses HHMMSS[Z] format for local or UTC times
- **UtcOffsetParser:** Parses [+/-]HHMM[SS] timezone offsets
- **PeriodParser:** Parses period values (start/end or start/duration)
- **RecurParser:** Parses RRULE patterns with FREQ, COUNT, UNTIL, INTERVAL, BY* components

**Test Requirements:**
```php
// Tests pass for each parser:
testParseValidFormat() ✓
testParseInvalidFormat() ✓
testParseEdgeCases() ✓
```

---

## Epic 5: Component System

**Description:** Implement all component classes (VCALENDAR, VEVENT, VTODO, VJOURNAL, VFREEBUSY, VTIMEZONE, VALARM).

**Why Important:** Components are the structured containers for calendar data.

**Current Status:** In Progress

---

### Task 5.1: Component Base Classes

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 6 hours

**Description:** Create AbstractComponent and ComponentInterface that provide common functionality for all components.

**Acceptance Criteria:**
- [x] AbstractComponent provides property storage
- [x] ComponentInterface defines required methods
- [x] Support for adding/removing properties
- [x] Support for adding/removing sub-components
- [x] Property access by name
- [x] Component iteration support

**Dependencies:** Task 4.4

**Implementation Details:**
- **ComponentInterface:** Defines contracts for all components with methods for properties and sub-components
- **AbstractComponent:** Provides common functionality including property storage, sub-component management, and parent tracking
- **GenericProperty:** Helper class for creating simple string properties
- **TextValue:** Value class for text-based properties

**New Files Created:**
- `/var/www/html/phpical/src/Property/GenericProperty.php` - Generic property implementation
- `/var/www/html/phpical/src/Value/TextValue.php` - Text value implementation

**Test Requirements:**
```php
// Tests pass:
testAbstractComponentPropertyStorage() ✓
testComponentInterfaceContract() ✓
testComponentPropertyAccess() ✓
testComponentSubComponents() ✓
testComponentIteration() ✓
```

---

### Task 5.2: VCALENDAR Component

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 4 hours

**Description:** Implement VCALENDAR root component with required PRODID and VERSION properties.

**Why Important:** VCALENDAR is the root container for all iCalendar data.

**Acceptance Criteria:**
- [x] Require PRODID property (error ICAL-COMP-001 if missing)
- [x] Require VERSION property (error ICAL-COMP-002 if missing)
- [x] Support optional CALSCALE property
- [x] Support optional METHOD property
- [x] Support X- properties
- [x] Validate component structure

**Dependencies:** Task 5.1

**Implementation Details:**
- Created `VCalendar` class extending `AbstractComponent`
- Implements required PRODID and VERSION properties with validation
- Supports optional CALSCALE and METHOD properties
- Fluent interface for all setter methods
- Validation throws `ValidationException` with appropriate error codes

**Test Requirements:**
```php
// Tests pass:
testVCalendarRequiresProdId() ✓
testVCalendarRequiresVersion() ✓
testVCalendarSupportsCalscale() ✓
testVCalendarSupportsMethod() ✓
testVCalendarSupportsXProperties() ✓
testVCalendarValidation() ✓
```

---

### Task 5.3: VEVENT Component

**Status:** ✅ Completed  
**Priority:** HIGH  
**Effort:** 8 hours

**Description:** Implement VEVENT component with all required and recommended properties.

**Why Important:** VEVENT is the most commonly used component for calendar events.

**Acceptance Criteria:**
- [x] Require DTSTAMP property (error ICAL-VEVENT-001)
- [x] Require UID property (error ICAL-VEVENT-002)
- [x] Support all recommended properties
- [x] Validate DTEND/DURATION mutual exclusivity (error ICAL-VEVENT-VAL-001)
- [x] Validate DATE consistency (error ICAL-VEVENT-VAL-002)
- [x] Validate STATUS values (error ICAL-VEVENT-VAL-003)
- [x] Support VALARM sub-components

**Dependencies:** Task 5.2

**Implementation Details:**
- Created `VEvent` class extending `AbstractComponent`
- Implements required DTSTAMP and UID properties with validation
- Supports all recommended properties: DTSTART, DTEND, DURATION, RRULE, SUMMARY, DESCRIPTION, LOCATION, STATUS, CATEGORIES, URL, GEO
- Fluent interface for all setter methods
- Validation throws `ValidationException` with appropriate error codes
- Support for VALARM sub-components via addAlarm/getAlarms methods
- Created `VAlarm` class for alarm functionality

**Test Requirements:**
```php
// Tests pass:
testVEventRequiresDtStamp() ✓
testVEventRequiresUid() ✓
testVEventSupportsAllProperties() ✓
testVEventValidatesDtEndDuration() ✓
testVEventValidatesDateConsistency() ✓
testVEventValidatesStatus() ✓
testVEventSupportsAlarms() ✓
```

---

### Task 5.4: VTODO Component

**Status:** ✅ Completed
**Priority:** MEDIUM
**Effort:** 4 hours

**File:** `src/Component/VTodo.php`
**Test:** `tests/Component/VTodoTest.php`

**Description:** Implement VTODO component for task/to-do items.

**Why Important:** Supports task management functionality.

**Acceptance Criteria:**
- [x] Require DTSTAMP property (error ICAL-VTODO-001)
- [x] Require UID property (error ICAL-VTODO-002)
- [x] Support COMPLETED property (DATE-TIME in UTC)
- [x] Support DUE property (DATE or DATE-TIME)
- [x] Support PERCENT-COMPLETE property (0-100 integer)
- [x] Support DTSTART property
- [x] Support DURATION property (mutually exclusive with DUE, error ICAL-VTODO-VAL-001)
- [x] Support PRIORITY property (0-9, 0=undefined, 1=highest)
- [x] Support SUMMARY, DESCRIPTION, LOCATION, URL
- [x] Support CATEGORIES (comma-separated list)
- [x] Validate STATUS values: NEEDS-ACTION, COMPLETED, IN-PROCESS, CANCELLED (error ICAL-VTODO-VAL-002)
- [x] Support VALARM sub-components
- [x] Fluent interface for all setters

**Dependencies:** Task 5.1 (AbstractComponent)

**Can Parallelize With:** Tasks 5.5, 5.6

**Test Requirements:**
```php
// Tests must pass:
testVTodoRequiresDtStamp()
testVTodoRequiresUid()
testVTodoSupportsCompleted()
testVTodoSupportsDue()
testVTodoDueAndDurationMutuallyExclusive()
testVTodoSupportsPercentComplete()
testVTodoPercentCompleteRange()  // 0-100 only
testVTodoValidatesStatus()
testVTodoSupportsAlarms()
testVTodoFluentInterface()
```

---

### Task 5.5: VJOURNAL Component

**Status:** ✅ Completed
**Priority:** LOW
**Effort:** 2 hours

**File:** `src/Component/VJournal.php`
**Test:** `tests/Component/VJournalTest.php`

**Description:** Implement VJOURNAL component for journal entries.

**Why Important:** Supports journal functionality for calendar applications.

**Acceptance Criteria:**
- [x] Require DTSTAMP property (error ICAL-VJOURNAL-001)
- [x] Require UID property (error ICAL-VJOURNAL-002)
- [x] Support DTSTART property (DATE or DATE-TIME)
- [x] Support SUMMARY property
- [x] Support DESCRIPTION property (can be multiple)
- [x] Support CATEGORIES property
- [x] Support CLASS property (PUBLIC, PRIVATE, CONFIDENTIAL)
- [x] Validate STATUS values: DRAFT, FINAL, CANCELLED (error ICAL-VJOURNAL-VAL-001)
- [x] Support RRULE for recurring journal entries
- [x] Fluent interface for all setters

**Dependencies:** Task 5.1 (AbstractComponent)

**Can Parallelize With:** Tasks 5.4, 5.6

**Test Requirements:**
```php
// Tests must pass:
testVJournalRequiresDtStamp()
testVJournalRequiresUid()
testVJournalValidatesStatus()
testVJournalSupportsMultipleDescriptions()
testVJournalSupportsClass()
testVJournalFluentInterface()
```

---

### Task 5.6: VFREEBUSY Component

**Status:** ❌ Not Started
**Priority:** LOW
**Effort:** 3 hours

**File:** `src/Component/VFreeBusy.php`
**Test:** `tests/Component/VFreeBusyTest.php`

**Description:** Implement VFREEBUSY component for availability information.

**Why Important:** Supports free/busy time exchange between calendar systems.

**Acceptance Criteria:**
- [ ] Require DTSTAMP property (error ICAL-VFB-001)
- [ ] Require UID property (error ICAL-VFB-002)
- [ ] Support DTSTART property (start of freebusy time range)
- [ ] Support DTEND property (end of freebusy time range)
- [ ] Support CONTACT property
- [ ] Support ORGANIZER property (for published freebusy)
- [ ] Support ATTENDEE property
- [ ] Support FREEBUSY property with FBTYPE parameter:
  - FBTYPE=FREE (default)
  - FBTYPE=BUSY
  - FBTYPE=BUSY-UNAVAILABLE
  - FBTYPE=BUSY-TENTATIVE
- [ ] FREEBUSY value is comma-separated list of PERIOD values
- [ ] Validate PERIOD values in FREEBUSY (error ICAL-VFB-VAL-001)
- [ ] Support multiple FREEBUSY properties
- [ ] Fluent interface for all setters

**Dependencies:** Task 5.1 (AbstractComponent), Task 4.4 (PeriodParser)

**Can Parallelize With:** Tasks 5.4, 5.5

**Test Requirements:**
```php
// Tests must pass:
testVFreeBusyRequiresDtStamp()
testVFreeBusyRequiresUid()
testVFreeBusSupportsFreebusy()
testVFreeBusyFbtypeFree()
testVFreeBusyFbtypeBusy()
testVFreeBusyFbtypeBusyTentative()
testVFreeBusyMultiplePeriods()
testVFreeBusyValidatesPeriods()
testVFreeBusyFluentInterface()
```

---

### Task 5.7: VTIMEZONE Component

**Status:** ❌ Not Started
**Priority:** HIGH
**Effort:** 10 hours

**File:** `src/Component/VTimezone.php`
**Test:** `tests/Component/VTimezoneTest.php`

**Description:** Implement VTIMEZONE component with timezone observance rules. This is one of the most complex components.

**Why Important:** Critical for handling timezone-aware events correctly. Without proper timezone handling, recurring events and cross-timezone scheduling will be incorrect.

**Acceptance Criteria:**
- [ ] Require TZID property (error ICAL-TZ-001)
- [ ] Require at least one STANDARD or DAYLIGHT sub-component (error ICAL-TZ-002)
- [ ] Support optional LAST-MODIFIED property
- [ ] Support optional TZURL property (URL to updated timezone definition)
- [ ] Accept STANDARD sub-components (Task 5.9)
- [ ] Accept DAYLIGHT sub-components (Task 5.9)
- [ ] Build transition table from observances:
  ```php
  // Example transition table for America/New_York
  [
    ['time' => '2026-03-08T02:00:00', 'offset' => -14400, 'name' => 'EDT'],  // Spring forward
    ['time' => '2026-11-01T02:00:00', 'offset' => -18000, 'name' => 'EST'],  // Fall back
  ]
  ```
- [ ] `getOffsetAt(DateTimeInterface $dt): int` - returns offset in seconds
- [ ] `getAbbreviationAt(DateTimeInterface $dt): string` - returns TZNAME
- [ ] Handle infinite recurring observances (RRULE in STANDARD/DAYLIGHT)
- [ ] Map TZID to PHP DateTimeZone when possible (e.g., "America/New_York")
- [ ] Fluent interface for setters

**Dependencies:** Task 5.9 (Standard/Daylight observances)

**Blocked By:** Task 5.9

**Test Requirements:**
```php
// Tests must pass:
testVTimezoneRequiresTzid()
testVTimezoneRequiresObservance()
testVTimezoneAcceptsStandard()
testVTimezoneAcceptsDaylight()
testVTimezoneBuildTransitionTable()
testVTimezoneGetOffsetAt()
testVTimezoneGetAbbreviationAt()
testVTimezoneHandlesDstSpringForward()
testVTimezoneHandlesDstFallBack()
testVTimezoneRecurringObservance()
testVTimezoneMapsToPhpTimezone()
testVTimezoneFluentInterface()
```

**Implementation Hint:**
```php
// Example structure
class VTimezone extends AbstractComponent
{
    private array $transitions = [];

    public function addStandard(Standard $standard): self { ... }
    public function addDaylight(Daylight $daylight): self { ... }

    public function buildTransitions(?DateTimeInterface $start = null, ?DateTimeInterface $end = null): void
    {
        // Generate transitions from STANDARD/DAYLIGHT RRULE/RDATE
        // Sort by time ascending
    }

    public function getOffsetAt(DateTimeInterface $dt): int
    {
        // Binary search transitions for $dt
        // Return offset of matching transition
    }
}
```

---

### Task 5.8: VALARM Component

**Status:** ✅ Completed (Basic) / 🔄 Needs Enhancement
**Priority:** MEDIUM
**Effort:** 6 hours (2 hours remaining for validation enhancement)

**File:** `src/Component/VAlarm.php`

**Description:** Implement VALARM component for event reminders.

**Why Important:** Supports alarm/reminder functionality.

**Current Implementation:**
- [x] Basic VAlarm class with ACTION, TRIGGER, DURATION, REPEAT, DESCRIPTION, SUMMARY, ATTENDEE properties
- [x] Basic validation for ACTION and TRIGGER presence
- [x] Fluent interface setters/getters

**Remaining Acceptance Criteria:**
- [x] Require ACTION property (error ICAL-ALARM-001) ✅
- [x] Require TRIGGER property (error ICAL-ALARM-002) ✅
- [ ] Validate ACTION is one of: AUDIO, DISPLAY, EMAIL (error ICAL-ALARM-VAL-002)
- [ ] Validate action-specific requirements:
  - DISPLAY: requires DESCRIPTION (error ICAL-ALARM-003)
  - EMAIL: requires SUMMARY, DESCRIPTION, ATTENDEE (error ICAL-ALARM-004)
  - AUDIO: optional ATTACH
- [ ] Validate REPEAT and DURATION must both be present or both absent (error ICAL-ALARM-VAL-001)
- [ ] Support ATTACH property for AUDIO action

**Dependencies:** Task 5.1

**Test Requirements:**
```php
// Tests must pass:
testVAlarmRequiresAction() ✓
testVAlarmRequiresTrigger() ✓
testVAlarmValidatesActionType()
testVAlarmDisplayRequiresDescription()
testVAlarmEmailRequiresProperties()
testVAlarmAudioSupportsAttach()
testVAlarmSupportsRepeat()
testVAlarmRepeatDurationMutualRequirement()
```

---

### Task 5.9: Timezone Observance Components (Standard/Daylight)

**Status:** ✅ Completed (Basic) / 🔄 Needs Enhancement
**Priority:** HIGH
**Effort:** 4 hours (2 hours remaining)

**Files:** `src/Component/Standard.php`, `src/Component/Daylight.php`

**Description:** Implement STANDARD and DAYLIGHT observance sub-components for VTIMEZONE.

**Why Important:** Required for VTIMEZONE to define timezone transitions.

**Current Implementation:**
- [x] Basic Standard class extending AbstractComponent
- [x] Basic Daylight class extending AbstractComponent

**Remaining Acceptance Criteria:**
- [ ] Require DTSTART property (error ICAL-TZ-OBS-001)
- [ ] Require TZOFFSETTO property (error ICAL-TZ-OBS-002)
- [ ] Require TZOFFSETFROM property (error ICAL-TZ-OBS-003)
- [ ] Support optional RRULE for recurring transitions
- [ ] Support optional RDATE for one-time transitions
- [ ] Support optional TZNAME (e.g., "EST", "EDT")
- [ ] Support optional COMMENT

**Dependencies:** Task 5.1

**Test Requirements:**
```php
testStandardRequiresDtstart()
testStandardRequiresTzoffsetto()
testStandardRequiresTzoffsetfrom()
testDaylightRequiresDtstart()
testDaylightSupportsRrule()
testDaylightSupportsTzname()
```

---

### Task 5.10: Lexer Implementation

**Status:** ❌ Not Started
**Priority:** HIGH
**Effort:** 6 hours

**File:** `src/Parser/Lexer.php`

**Description:** Implement Lexer that tokenizes raw iCalendar data into ContentLine objects using generator pattern.

**Why Important:** Foundation for streaming parser. Enables constant-memory parsing of large files.

**Acceptance Criteria:**
- [ ] `tokenize(string $data): \Generator<ContentLine>` - tokenize string data
- [ ] `tokenizeFile(string $filepath): \Generator<ContentLine>` - stream from file
- [ ] Normalize line endings (LF, CR, CRLF → CRLF)
- [ ] Unfold continuation lines (CRLF + space/tab)
- [ ] Track line numbers for error reporting
- [ ] Handle files larger than available memory (streaming)
- [ ] Detect and report malformed lines (missing colon)

**Dependencies:** Task 2.1 (ContentLine), Task 2.2 (LineFolder)

**Test Requirements:**
```php
testTokenizeSimpleCalendar()
testTokenizeWithFoldedLines()
testTokenizeNormalizesLineEndings()
testTokenizeTracksLineNumbers()
testTokenizeFileStreaming()
testTokenizeLargeFile()
testTokenizeDetectsMalformedLine()
```

---

### Task 5.11: Security Hardening

**Status:** ❌ Not Started
**Priority:** HIGH
**Effort:** 8 hours

**Files:** `src/Validation/SecurityValidator.php`, `src/Parser/Parser.php` (add depth tracking)

**Description:** Implement security requirements NFR-010 through NFR-013.

**Why Important:** Prevents XXE, SSRF, and DoS attacks.

**Acceptance Criteria:**
- [ ] Create SecurityValidator class with all security checks
- [ ] NFR-010: Validate ATTACH URI schemes (block file://, restrict data:)
- [ ] NFR-011: Track and limit recursion depth (default 100, configurable)
- [ ] NFR-012: Validate URIs to prevent SSRF (block private IPs, restrict schemes)
- [ ] NFR-013: Sanitize text output (strip null bytes, escape control chars)
- [ ] Add `Parser::setMaxDepth(int $depth)` configuration
- [ ] Throw ICAL-SEC-001 for depth exceeded
- [ ] Throw ICAL-SEC-002 for XXE attempt
- [ ] Throw ICAL-SEC-003 for SSRF attempt

**Dependencies:** Task 5.10 (Lexer)

**Test Requirements:**
```php
testSecurityDepthLimit()
testSecurityDepthExceeded()
testSecurityXxeBlocked()
testSecuritySsrfPrivateIpBlocked()
testSecuritySsrfFileSchemeBlocked()
testSecurityTextSanitization()
testSecurityNullByteStripped()
```

---

## Epic 6: Recurrence Rule System

**Description:** Implement RRULE parsing and recurrence instance generation.

**Why Important:** Recurring events are a core calendar feature. Complex but essential.

---

### Task 6.1: RRULE Parser

**Status:** ❌ Not Started
**Priority:** HIGH
**Effort:** 12 hours

**File:** `src/Recurrence/RRuleParser.php`, `src/Recurrence/RRule.php`

**Description:** Create RRuleParser that parses RRULE strings into structured RRule objects.

**Why Important:** RRULE strings define complex recurring patterns. Accurate parsing is critical.

**Acceptance Criteria:**
- [ ] Parse FREQ component (SECONDLY, MINUTELY, HOURLY, DAILY, WEEKLY, MONTHLY, YEARLY)
- [ ] Parse INTERVAL modifier (default 1)
- [ ] Parse UNTIL (DATE-TIME value) - mutually exclusive with COUNT
- [ ] Parse COUNT (positive integer) - mutually exclusive with UNTIL
- [ ] Parse WKST (SU, MO, TU, WE, TH, FR, SA - default MO)
- [ ] Parse BYSECOND (0-60, comma-separated)
- [ ] Parse BYMINUTE (0-59, comma-separated)
- [ ] Parse BYHOUR (0-23, comma-separated)
- [ ] Parse BYDAY (SU-SA with optional +/-n prefix, e.g., "2TU" for 2nd Tuesday)
- [ ] Parse BYMONTHDAY (1-31 or -31 to -1, comma-separated)
- [ ] Parse BYYEARDAY (1-366 or -366 to -1, comma-separated)
- [ ] Parse BYWEEKNO (1-53 or -53 to -1, comma-separated)
- [ ] Parse BYMONTH (1-12, comma-separated)
- [ ] Parse BYSETPOS (1-366 or -366 to -1, filters BY* results)
- [ ] Validate UNTIL and COUNT mutual exclusivity (error ICAL-RRULE-003)
- [ ] Validate FREQ is required (error ICAL-RRULE-001)
- [ ] Return immutable RRule value object with all parsed components

**Dependencies:** Task 4.1 (DateTimeParser for UNTIL), Task 4.4 (RecurParser stub to enhance)

**Test Vectors:**
```
# Input → Expected parsed values
"FREQ=DAILY;COUNT=10" → freq=DAILY, count=10
"FREQ=WEEKLY;BYDAY=MO,WE,FR;UNTIL=20261231T235959Z" → freq=WEEKLY, byday=[MO,WE,FR], until=DateTimeImmutable
"FREQ=MONTHLY;BYDAY=2TU" → freq=MONTHLY, byday=[{ordinal:2,day:TU}]
"FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=-1" → freq=YEARLY, bymonth=[2], bymonthday=[-1]
"FREQ=WEEKLY;INTERVAL=2;WKST=SU" → freq=WEEKLY, interval=2, wkst=SU
```

**Test Requirements:**
```php
// Tests must pass:
testParseFreqDaily()
testParseFreqWeekly()
testParseFreqMonthly()
testParseFreqYearly()
testParseFreqSecondly()
testParseInterval()
testParseUntil()
testParseCount()
testParseUntilAndCountMutuallyExclusive()
testParseBySecond()
testParseByMinute()
testParseByHour()
testParseByDay()
testParseBydayWithOrdinal()  // "2TU", "-1FR"
testParseByMonthDay()
testParseByMonthDayNegative()  // -1 = last day
testParseByYearDay()
testParseByWeekNo()
testParseByMonth()
testParseBySetPos()
testParseWkst()
testParseComplexRrule()
testParseInvalidRruleMissingFreq()
testParseInvalidRruleUnknownComponent()
testRRuleIsImmutable()
```

---

### Task 6.2: Recurrence Generator

**Status:** ❌ Not Started
**Priority:** HIGH
**Effort:** 16 hours

**File:** `src/Recurrence/RecurrenceGenerator.php`

**Description:** Create RecurrenceGenerator that generates occurrence instances from RRULE patterns using generator/iterator pattern for memory efficiency.

**Why Important:** Transforms RRULE patterns into actual event dates/times. This is one of the most complex parts of iCalendar.

**Acceptance Criteria:**
- [ ] `generate(RRule $rule, DateTimeInterface $dtstart, ?DateTimeInterface $rangeEnd = null): \Generator<DateTimeImmutable>`
- [ ] Generate instances for all FREQ types (SECONDLY through YEARLY)
- [ ] Apply INTERVAL correctly (every N periods)
- [ ] Stop at COUNT limit
- [ ] Stop at UNTIL datetime
- [ ] Apply BYDAY filter (including ordinal like "2TU")
- [ ] Apply BYMONTHDAY filter (including negative values)
- [ ] Apply BYMONTH filter
- [ ] Apply BYSETPOS to filter BY* results
- [ ] Handle timezone-aware generation - preserve DTSTART timezone
- [ ] Support EXDATE exceptions (exclude specific dates)
- [ ] Support RDATE additions (add specific dates)
- [ ] Implement `\Generator` pattern (yield one instance at a time)
- [ ] Handle leap years correctly (Feb 29)
- [ ] Handle DST transitions (clocks spring forward/fall back)
- [ ] Handle month length variations (Jan 31 → Feb 28)

**Dependencies:** Task 6.1, Task 5.7 (VTIMEZONE for timezone resolution)

**Test Vectors (RFC 5545 Examples):**
```php
// Daily for 10 occurrences
$rule = RRule::parse('FREQ=DAILY;COUNT=10');
$dtstart = new DateTimeImmutable('2026-01-01T09:00:00');
// → 2026-01-01, 01-02, 01-03, ..., 01-10 (exactly 10)

// Weekly on Tuesday and Thursday for 5 weeks
$rule = RRule::parse('FREQ=WEEKLY;COUNT=10;BYDAY=TU,TH');
$dtstart = new DateTimeImmutable('2026-01-01T09:00:00');  // Wednesday
// → Jan 2 (Thu), Jan 6 (Tue), Jan 8 (Thu), Jan 13 (Tue), ...

// Monthly on 2nd Tuesday
$rule = RRule::parse('FREQ=MONTHLY;BYDAY=2TU;COUNT=6');
$dtstart = new DateTimeImmutable('2026-01-01T09:00:00');
// → Jan 13, Feb 10, Mar 10, Apr 14, May 12, Jun 9

// Yearly on last day of February (leap year handling)
$rule = RRule::parse('FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=-1;COUNT=4');
$dtstart = new DateTimeImmutable('2026-01-01T09:00:00');
// → Feb 28 2026, Feb 28 2027, Feb 29 2028, Feb 28 2029

// Every other week on Monday, Wednesday, Friday
$rule = RRule::parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR;COUNT=10');
$dtstart = new DateTimeImmutable('2026-01-05T09:00:00');  // Monday
// → Jan 5 (Mon), Jan 7 (Wed), Jan 9 (Fri), Jan 19 (Mon), Jan 21 (Wed), ...

// With EXDATE exclusion
$rule = RRule::parse('FREQ=DAILY;COUNT=5');
$dtstart = new DateTimeImmutable('2026-01-01');
$exdates = [new DateTimeImmutable('2026-01-03')];
// → Jan 1, Jan 2, Jan 4, Jan 5, Jan 6 (Jan 3 excluded, so 6th fills COUNT)
```

**Test Requirements:**
```php
// Tests must pass:
testGenerateDailyInstances()
testGenerateDailyWithCount()
testGenerateDailyWithUntil()
testGenerateWeeklyInstances()
testGenerateWeeklyWithByday()
testGenerateWeeklyWithInterval()
testGenerateMonthlyInstances()
testGenerateMonthlyWithBydayOrdinal()  // "2TU"
testGenerateMonthlyWithBymonthday()
testGenerateMonthlyWithNegativeBymonthday()  // -1 = last
testGenerateYearlyInstances()
testGenerateYearlyBymonthBymonthday()
testGenerateWithBysetpos()
testGenerateWithExdate()
testGenerateWithRdate()
testGenerateTimezonePreserved()
testGenerateLeapYearFeb29()
testGenerateDSTSpringForward()
testGenerateDSTFallBack()
testGenerateMonthOverflow()  // Jan 31 monthly → Feb 28
testGeneratorYieldsOneAtATime()
testGeneratorStopsAtRangeEnd()
```

---

## Epic 7: Writer System

**Description:** Implement the writer system that serializes components back to iCalendar format.

**Why Important:** Enables the library to output valid iCalendar data.

---

### Task 7.1: Value Writers

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort:** 8 hours

**Description:** Create value writer classes that serialize PHP values back to iCalendar format.

**Why Important:** Ensures output format matches RFC 5545 specifications.

**Acceptance Criteria:**
- [ ] DateWriter: format YYYYMMDD
- [ ] DateTimeWriter: format YYYYMMDDTHHMMSS[Z] or with TZID
- [ ] TextWriter: escape special characters
- [ ] DurationWriter: format ISO 8601 duration
- [ ] BinaryWriter: Base64 encode with line wrapping
- [ ] All other data type writers
- [ ] Handle null/empty values correctly

**Dependencies:** Task 6.2

**Test Requirements:**
```php
// Tests must pass:
testWriteDate()
testWriteDateTimeUtc()
testWriteDateTimeWithTzid()
testWriteTextEscaping()
testWriteDuration()
testWriteBinary()
testWriteAllTypes()
```

---

### Task 7.2: Property Writer

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort:** 6 hours

**Description:** Create PropertyWriter that serializes properties with parameters and values.

**Why Important:** Handles property formatting according to RFC 5545.

**Acceptance Criteria:**
- [ ] Serialize property name
- [ ] Serialize parameters with proper quoting
- [ ] Apply RFC 6868 encoding for parameter values
- [ ] Serialize values using appropriate value writer
- [ ] Handle multi-valued parameters
- [ ] Handle properties without parameters

**Dependencies:** Task 7.1

**Test Requirements:**
```php
// Tests must pass:
testWriteSimpleProperty()
testWritePropertyWithParameters()
testWritePropertyWithQuotedParameter()
testWritePropertyWithRfc6868()
testWritePropertyWithMultiValue()
testWritePropertyWithoutParameters()
```

---

### Task 7.3: Component Writer

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort:** 8 hours

**Description:** Create ComponentWriter that serializes components with BEGIN/END markers.

**Why Important:** Outputs complete iCalendar components.

**Acceptance Criteria:**
- [ ] Generate BEGIN/END markers for all component types
- [ ] Serialize properties in logical order
- [ ] Include required properties for each component type
- [ ] Serialize sub-components recursively
- [ ] Handle empty components correctly
- [ ] Generate valid VCALENDAR wrapper

**Dependencies:** Task 7.2

**Test Requirements:**
```php
// Tests must pass:
testWriteVCalendar()
testWriteVEvent()
testWriteVTodo()
testWriteVJournal()
testWriteVFreeBusy()
testWriteVTimezone()
testWriteVAlarm()
testWriteComponentWithSubComponents()
testWriteEmptyComponent()
```

---

### Task 7.4: Line Folding Writer

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort:** 4 hours

**Description:** Create ContentLineWriter that handles line folding for output.

**Why Important:** Ensures output lines conform to RFC 5545 line length limits.

**Acceptance Criteria:**
- [ ] Fold lines longer than 75 octets
- [ ] Use CRLF line endings
- [ ] Never fold within UTF-8 sequences
- [ ] Fold at logical boundaries when possible
- [ ] Handle very long lines correctly

**Dependencies:** Task 7.3

**Test Requirements:**
```php
// Tests must pass:
testFoldLongLine()
testFoldCrlfEndings()
testFoldUtf8Sequence()
testFoldAtBoundary()
testFoldVeryLongLine()
```

---

## Epic 8: Main Parser & Writer

**Description:** Implement the main Parser and Writer classes that tie everything together.

**Why Important:** Provides the primary API for users of the library.

---

### Task 8.1: Main Parser

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort**: 12 hours

**Description:** Create Parser class that implements ParserInterface and orchestrates the parsing process.

**Why Important:** Main entry point for parsing iCalendar data.

**Acceptance Criteria:**
- [ ] Implement parse() method for string input
- [ ] Implement parseFile() method for file input
- [ ] Implement setStrict() for strict/lenient parsing
- [ ] Implement getErrors() for non-fatal warnings
- [ ] Handle all ICAL error codes appropriately
- [ ] Support streaming for large files (generator pattern)
- [ ] Validate final calendar structure

**Dependencies:** Task 7.4

**Test Requirements:**
```php
// Tests must pass:
testParseSimpleEvent()
testParseComplexCalendar()
testParseFile()
testParseStrictMode()
testParseLenientMode()
testParseErrors()
testParseLargeFile()
testParseRoundTrip()
```

---

### Task 8.2: Main Writer

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort**: 8 hours

**Description:** Create Writer class that implements WriterInterface and orchestrates the writing process.

**Why Important:** Main entry point for generating iCalendar output.

**Acceptance Criteria:**
- [ ] Implement write() method for string output
- [ ] Implement writeToFile() method for file output
- [ ] Implement setLineFolding() for configuration
- [ ] Handle all ICAL-WRITE error codes
- [ ] Support streaming output for large calendars
- [ ] Validate calendar before writing

**Dependencies:** Task 8.1

**Test Requirements:**
```php
// Tests must pass:
testWriteSimpleEvent()
testWriteComplexCalendar()
testWriteToFile()
testWriteLineFolding()
testWriteStreaming()
testWriteValidation()
testWriteRoundTrip()
```

---

## Epic 9: Validation System

**Description:** Implement validation rules and error reporting for calendar components.

**Why Important:** Ensures generated calendars are valid and interoperable.

---

### Task 9.1: Validation Rules

**Status:** ❌ Not Started  
**Priority:** MEDIUM  
**Effort**: 10 hours

**Description:** Create validation rules for all component types and constraints.

**Why Important:** Catches common errors and ensures RFC compliance.

**Acceptance Criteria:**
- [ ] Validate required properties for all components
- [ ] Validate mutual exclusivity constraints (e.g., DTEND/DURATION)
- [ ] Validate value ranges and formats
- [ ] Validate timezone references
- [ ] Validate recurrence rule combinations
- [ ] Return ValidationError collection

**Dependencies:** Task 8.2

**Test Requirements:**
```php
// Tests must pass:
testValidateRequiredProperties()
testValidateMutualExclusivity()
testValidateValueRanges()
testValidateTimezoneReferences()
testValidateRecurrenceRules()
testValidationErrorCollection()
```

---

### Task 9.2: Validator Class

**Status:** ❌ Not Started  
**Priority:** MEDIUM  
**Effort**: 4 hours

**Description:** Create Validator class that applies validation rules to calendars.

**Why Important:** Provides user-friendly validation API.

**Acceptance Criteria:**
- [ ] Implement validate() method
- [ ] Return ValidationError collection
- [ ] Support component-level validation
- [ ] Support property-level validation
- [ ] Provide clear error messages

**Dependencies:** Task 9.1

**Test Requirements:**
```php
// Tests must pass:
testValidatorValidatesCalendar()
testValidatorReturnsErrors()
testValidatorComponentValidation()
testValidatorPropertyValidation()
testValidatorErrorMessages()
```

---

## Epic 10: Test Suite

**Description:** Create comprehensive test suite covering all functionality.

**Why Important:** Ensures code quality and RFC compliance.

---

### Task 10.1: Unit Tests

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort**: 40 hours

**Description:** Create unit tests for all classes with 100% coverage.

**Why Important:** Foundation of test suite, catches regressions.

**Acceptance Criteria:**
- [ ] 100% code coverage for all parsers
- [ ] 100% code coverage for all writers
- [ ] 100% code coverage for all components
- [ ] All error code paths tested
- [ ] All edge cases tested

**Dependencies:** Task 9.2

**Test Requirements:**
- All tests in PHPUnit pass
- Coverage report shows 100%

---

### Task 10.2: RFC 5545 Conformance Tests

**Status:** ❌ Not Started
**Priority:** HIGH
**Effort:** 20 hours

**Files:**
- `tests/Conformance/Rfc5545ExamplesTest.php`
- `tests/fixtures/rfc5545/*.ics` (test data files)

**Description:** Create tests for all RFC 5545 examples and conformance requirements.

**Why Important:** Verifies RFC compliance. These are the canonical test cases.

**Acceptance Criteria:**
- [ ] Test all 14 RFC 5545 examples (see list below)
- [ ] Parse and round-trip each example
- [ ] Verify output matches specification (ignoring whitespace/ordering)
- [ ] Test all data types with examples
- [ ] Create fixture files for each example

**RFC 5545 Example Files to Create:**
1. `simple-event.ics` - Basic VEVENT with required properties
2. `daily-recurring.ics` - RRULE:FREQ=DAILY
3. `weekly-with-exceptions.ics` - RRULE with EXDATE
4. `monthly-byday.ics` - Monthly on 2nd Tuesday
5. `yearly-recurring.ics` - Yearly birthday
6. `all-day-event.ics` - VALUE=DATE (no time)
7. `todo-with-due.ics` - VTODO with DUE
8. `journal-entry.ics` - VJOURNAL
9. `freebusy.ics` - VFREEBUSY
10. `timezone-dst.ics` - VTIMEZONE with STANDARD/DAYLIGHT
11. `alarm-display.ics` - VALARM ACTION=DISPLAY
12. `alarm-email.ics` - VALARM ACTION=EMAIL
13. `alarm-audio.ics` - VALARM ACTION=AUDIO
14. `complex-meeting.ics` - Multiple ATTENDEEs, ORGANIZER, RRULE

**Round-Trip Test Strategy:**
```php
public function testRoundTrip(string $fixture): void
{
    $original = file_get_contents($fixture);
    $calendar = $this->parser->parse($original);
    $output = $this->writer->write($calendar);
    $reparsed = $this->parser->parse($output);

    // Compare calendars semantically (not string comparison)
    $this->assertCalendarsEquivalent($calendar, $reparsed);
}
```

**Dependencies:** Task 10.1, Task 8.2 (Main Writer)

**Test Requirements:**
```php
// Tests must pass:
testRfcExample1SimpleEvent()
testRfcExample2DailyRecurring()
// ... through testRfcExample14()
testAllDataTypesRoundTrip()
testRoundTripPreservesProperties()
testRoundTripPreservesComponents()
testRoundTripPreservesParameters()
```

---

### Task 10.3: Edge Case Tests

**Status:** ❌ Not Started  
**Priority:** MEDIUM  
**Effort**: 16 hours

**Description:** Create tests for edge cases, malformed input, and stress conditions.

**Why Important:** Ensures robustness.

**Acceptance Criteria:**
- [ ] Test empty values
- [ ] Test maximum line lengths
- [ ] Test maximum nesting depth
- [ ] Test Unicode in various scripts
- [ ] Test malformed folding
- [ ] Test invalid dates/times
- [ ] Test stress conditions (10K events)

**Dependencies:** Task 10.2

**Test Requirements:**
- All edge case tests pass
- Memory usage stable under stress

---

### Task 10.4: Performance Tests

**Status:** ❌ Not Started  
**Priority:** MEDIUM  
**Effort**: 8 hours

**Description:** Create performance benchmarks and tests.

**Why Important:** Verifies performance requirements.

**Acceptance Criteria:**
- [ ] Parse 10MB file in < 2 seconds
- [ ] Handle 10,000 events with < 128MB memory
- [ ] Streaming parser maintains constant memory
- [ ] Performance regression tests

**Dependencies:** Task 10.3

**Test Requirements:**
- All performance tests meet targets
- Benchmark suite runs successfully

---

## Epic 11: Documentation

**Description:** Create comprehensive documentation for the library.

**Why Important:** Enables users to understand and use the library effectively.

---

### Task 11.1: PHPDoc

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort**: 12 hours

**Description:** Add complete PHPDoc to all public APIs.

**Why Important:** Provides in-code documentation and IDE support.

**Acceptance Criteria:**
- [ ] All public classes documented
- [ ] All public methods documented
- [ ] All parameters and return values documented
- [ ] All exceptions documented
- [ ] Examples for complex methods

**Dependencies:** Task 10.4

**Test Requirements:**
- PHPDocumentor generates without errors
- All API documentation complete

---

### Task 11.2: README

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort**: 4 hours

**Description:** Create comprehensive README with quick start guide.

**Why Important:** First point of contact for users.

**Acceptance Criteria:**
- [ ] Installation instructions
- [ ] Quick start examples
- [ ] API overview
- [ ] Common use cases
- [ ] Links to full documentation

**Dependencies:** Task 11.1

---

### Task 11.3: Usage Guide

**Status:** ❌ Not Started  
**Priority:** MEDIUM  
**Effort**: 8 hours

**Description:** Create detailed usage guide with examples.

**Why Important:** Helps users with advanced features.

**Acceptance Criteria:**
- [ ] Detailed parsing examples
- [ ] Detailed writing examples
- [ ] Error handling guide
- [ ] Extension guide
- [ ] Migration guide from other libraries

**Dependencies:** Task 11.2

---

## Progress Summary

### Overall Progress: 23% (19/84 tasks complete)

#### Epic Progress:
- Epic 1: Foundation - 100% (3/3 tasks) ✅
- Epic 2: Content Line Processing - 100% (3/3 tasks) ✅
- Epic 3: Property Parsing - 100% (3/3 tasks) ✅
- Epic 4: Data Type Parsers - 100% (4/4 tasks) ✅
- Epic 5: Component System - 64% (7/11 tasks) 🔄
- Epic 6: Recurrence Rules - 0% (0/2 tasks)
- Epic 7: Writer System - 0% (0/4 tasks)
- Epic 8: Main Parser/Writer - 0% (0/2 tasks)
- Epic 9: Validation - 0% (0/2 tasks)
- Epic 10: Test Suite - 0% (0/4 tasks)
- Epic 11: Documentation - 0% (0/3 tasks)

### Parallel Work Opportunities

The following task groups can be worked on **in parallel** by multiple agents:

**Group A (Components - can all run in parallel):**
- Task 5.4: VTODO ✅
- Task 5.5: VJOURNAL ✅
- Task 5.6: VFREEBUSY
- Task 5.8: VALARM enhancement

**Group B (Infrastructure - after Group A):**
- Task 5.7: VTIMEZONE (needs 5.9 first)
- Task 5.9: Standard/Daylight
- Task 5.10: Lexer
- Task 5.11: Security

**Group C (Recurrence + Writer - after Epic 5):**
- Task 6.1: RRULE Parser
- Task 7.1: Value Writers (can start in parallel with 6.1)

### Next Steps:
1. **Next:** Task 5.6 VFREEBUSY
2. **Sequential:** Complete 5.9 before 5.7 (VTIMEZONE depends on observances)
3. **Parallel:** Tasks 5.10 (Lexer) and 5.11 (Security) after basic components

### Blocked Tasks:
- Task 5.7 (VTIMEZONE): Blocked by Task 5.9 (Standard/Daylight)

### Epic 5 Summary:
Component system in progress:
- ✅ Task 5.1: Component Base Classes (AbstractComponent, ComponentInterface, GenericProperty, TextValue)
- ✅ Task 5.2: VCALENDAR Component
- ✅ Task 5.3: VEVENT Component (with VALARM support)
- ✅ Task 5.4: VTODO Component (with VALARM, PERCENT-COMPLETE, PRIORITY, DUE/DURATION mutual exclusivity)
- ✅ Task 5.5: VJOURNAL Component (with multiple DESCRIPTION support, CLASS, recurring entries)
- ⏳ Task 5.6: VFREEBUSY Component
- ⏳ Task 5.7: VTIMEZONE Component (blocked by 5.9)
- ✅ Task 5.8: VALARM Component (basic, needs enhancement)
- ✅ Task 5.9: Standard/Daylight (basic, needs enhancement)
- ⏳ Task 5.10: Lexer Implementation (NEW)
- ⏳ Task 5.11: Security Hardening (NEW)

### Epic 4 Summary:
All data type parsing tasks completed:
- ✅ Task 4.1: Date/Time Parsers (DATE and DATE-TIME parsing with full validation)
- ✅ Task 4.2: Text Parser (escape sequences, Unicode, RFC 5545 compliance)
- ✅ Task 4.3: Duration Parser (ISO 8601 durations, negative durations, DateInterval objects)
- ✅ Task 4.4: Remaining Data Type Parsers (INTEGER, FLOAT, BOOLEAN, BINARY, URI, CAL-ADDRESS, TIME, UTC-OFFSET, PERIOD, RECUR)

### Epic 3 Summary:
All property parsing tasks completed:
- ✅ Task 3.1: Property Parser (with RFC 5545 compliance, RFC 6868 support, quoted values, multi-valued params)
- ✅ Task 3.2: Parameter Parser (dedicated parameter parsing class used by PropertyParser)
- ✅ Task 3.3: Value Parser Factory (14 RFC 5545 data types supported, parser caching)

### Epic 2 Summary:
All content line processing tasks completed:
- ✅ Task 2.1: Content Line Class
- ✅ Task 2.2: Line Folding/Unfolding
- ✅ Task 2.3: Line Folding for Output

### Epic 1 Summary:
All foundation tasks completed:
- ✅ Task 1.1: Project Structure Setup
- ✅ Task 1.2: Exception Classes Implementation
- ✅ Task 1.3: Core Interfaces

---

## Notes for AI Agents

### Implementation Order:
Follow the epic order as listed. Each epic builds on the previous ones. However, **tasks within an epic can often be parallelized** - see "Parallel Work Opportunities" section above.

### File Naming Convention:
- Source: `src/{Namespace}/{ClassName}.php`
- Tests: `tests/{Namespace}/{ClassName}Test.php`
- Example: `src/Component/VTodo.php` → `tests/Component/VTodoTest.php`

### Test-Driven Development:
- Write tests before implementation for each task
- Ensure all acceptance criteria tests pass
- Maintain 100% test coverage
- Use PHPUnit data providers for repetitive test cases

### Error Handling:
- All error codes from PRD §6 must be implemented
- Use constants for error codes (e.g., `public const ERR_MISSING_UID = 'ICAL-VEVENT-002'`)
- Use proper exception types with context
- Include line numbers in parse errors
- **IMPORTANT:** Check PRD §6 for correct error codes - some implementations may have wrong codes

### Code Style:
- PSR-12 coding standards
- Fluent interface for setters (return `$this`)
- Use `DateTimeImmutable` not `DateTime`
- Use `\Generator` for streaming operations
- Prefer composition over inheritance where possible

### Performance:
- Keep memory usage low (streaming for large files)
- Cache parsed objects where appropriate
- Use generators for large datasets

### RFC Compliance:
- Follow RFC 5545 exactly
- Test against RFC examples
- Handle edge cases gracefully

---

## Known Issues / Technical Debt

### Code Issues to Fix:

| File | Issue | Resolution |
|------|-------|------------|
| `src/Parser/ValueParser/DurationParser.php:31` | Uses `ICAL-TYPE-020` | Change to `ICAL-TYPE-006` per PRD |
| `src/Exception/ParseException.php:32` | Has correct `ICAL-TYPE-006` | Keep as-is, update DurationParser to use it |

### Missing Tests:

| Component | Gap | Priority |
|-----------|-----|----------|
| VAlarm | Action-specific validation tests | HIGH |
| Standard/Daylight | Observance validation tests | HIGH |
| All parsers | Round-trip tests (parse → write → parse) | MEDIUM |

### Architecture Decisions Pending:

1. **Property type classes**: Currently only `GenericProperty` exists. Should we create specific property classes (e.g., `DtstartProperty`, `SummaryProperty`) or keep using generics?

2. **Value type classes**: Currently only `TextValue` exists. Should we create typed value classes (e.g., `DateTimeValue`, `DurationValue`) that wrap the parsed results?

3. **Streaming threshold**: At what file size should we automatically switch to streaming mode? Suggested: 1MB.

---

## Milestone Checkpoints

### Milestone 1: Parse-Only MVP (Epic 5 + 6 + 8.1)
- All components can be parsed
- RRULE can be parsed and instances generated
- Main Parser ties everything together
- **Deliverable:** Can parse any RFC 5545 compliant file

### Milestone 2: Round-Trip MVP (Milestone 1 + Epic 7 + 8.2)
- All components can be written
- Parse → Write produces valid output
- **Deliverable:** Can parse and re-serialize iCalendar files

### Milestone 3: Production Ready (All Epics)
- Full validation
- Complete test coverage
- Documentation
- **Deliverable:** Production-ready library

---

---

## Quick Start Checklist for AI Agents

Before starting any task:

1. [ ] Read this STATUS.md to understand current state
2. [ ] Check PRD.md §6 for error codes (use exact codes!)
3. [ ] Verify dependencies are completed
4. [ ] Check "Can Parallelize With" to optimize work
5. [ ] Create test file first (TDD)
6. [ ] Run `composer test` before and after changes
7. [ ] Run `composer phpstan` to check types
8. [ ] Update task status when starting/completing

When implementing a component:

1. [ ] Extend `AbstractComponent`
2. [ ] Implement `getName(): string`
3. [ ] Add error code constants (`public const ERR_* = 'ICAL-*'`)
4. [ ] Add property setters/getters with fluent interface
5. [ ] Implement `validate(): void` throwing `ValidationException`
6. [ ] Create test class with all acceptance criteria tests

---

**Last Updated:** 2026-02-05
**Next Review:** After Epic 5 completion