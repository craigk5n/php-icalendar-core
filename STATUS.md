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

**Status:** ❌ Not Started  
**Priority:** MEDIUM  
**Effort:** 4 hours

**Description:** Implement VTODO component for task/to-do items.

**Why Important:** Supports task management functionality.

**Acceptance Criteria:**
- [ ] Require DTSTAMP and UID properties
- [ ] Support COMPLETED property
- [ ] Support DUE property
- [ ] Support PERCENT-COMPLETE property
- [ ] Validate STATUS values (NEEDS-ACTION, COMPLETED, IN-PROCESS, CANCELLED)
- [ ] Support VALARM sub-components

**Dependencies:** Task 5.3

**Test Requirements:**
```php
// Tests must pass:
testVTodoRequiresDtStamp()
testVTodoRequiresUid()
testVTodoSupportsCompleted()
testVTodoSupportsDue()
testVTodoSupportsPercentComplete()
testVTodoValidatesStatus()
```

---

### Task 5.5: VJOURNAL Component

**Status:** ❌ Not Started  
**Priority:** LOW  
**Effort:** 2 hours

**Description:** Implement VJOURNAL component for journal entries.

**Why Important:** Supports journal functionality for calendar applications.

**Acceptance Criteria:**
- [ ] Require DTSTAMP and UID properties
- [ ] Validate STATUS values (DRAFT, FINAL, CANCELLED)
- [ ] Support all standard VJOURNAL properties

**Dependencies:** Task 5.4

**Test Requirements:**
```php
// Tests must pass:
testVJournalRequiresDtStamp()
testVJournalRequiresUid()
testVJournalValidatesStatus()
```

---

### Task 5.6: VFREEBUSY Component

**Status:** ❌ Not Started  
**Priority:** LOW  
**Effort:** 3 hours

**Description:** Implement VFREEBUSY component for availability information.

**Why Important:** Supports free/busy time exchange between calendar systems.

**Acceptance Criteria:**
- [ ] Require DTSTAMP and UID properties
- [ ] Support CONTACT and ORGANIZER for published freebusy
- [ ] Support FREEBUSY property with FBTYPE parameter
- [ ] Validate period values in FREEBUSY

**Dependencies:** Task 5.5

**Test Requirements:**
```php
// Tests must pass:
testVFreeBusyRequiresDtStamp()
testVFreeBusyRequiresUid()
testVFreeBusySupportsFreeBusy()
testVFreeBusyValidatesPeriods()
```

---

### Task 5.7: VTIMEZONE Component

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort:** 10 hours

**Description:** Implement VTIMEZONE component with timezone observance rules.

**Why Important:** Critical for handling timezone-aware events correctly.

**Acceptance Criteria:**
- [ ] Require TZID property (error ICAL-TZ-001)
- [ ] Require at least one STANDARD or DAYLIGHT sub-component (error ICAL-TZ-002)
- [ ] Support optional LAST-MODIFIED and TZURL
- [ ] Parse observance properties: DTSTART, TZOFFSETTO, TZOFFSETFROM
- [ ] Support optional RRULE, RDATE, TZNAME in observances
- [ ] Store timezone rules as transition table
- [ ] Resolve timezone-aware datetime values

**Dependencies:** Task 5.6

**Test Requirements:**
```php
// Tests must pass:
testVTimezoneRequiresTzid()
testVTimezoneRequiresObservance()
testVTimezoneSupportsObservanceProperties()
testVTimezoneStoresTransitions()
testVTimezoneResolvesDateTime()
testVTimezoneHandlesDst()
```

---

### Task 5.8: VALARM Component

**Status:** ❌ Not Started  
**Priority:** MEDIUM  
**Effort:** 6 hours

**Description:** Implement VALARM component for event reminders.

**Why Important:** Supports alarm/reminder functionality.

**Acceptance Criteria:**
- [ ] Require ACTION property (error ICAL-ALARM-001)
- [ ] Require TRIGGER property (error ICAL-ALARM-002)
- [ ] Support AUDIO, DISPLAY, EMAIL actions
- [ ] Validate action-specific requirements:
  - DISPLAY: requires DESCRIPTION (error ICAL-ALARM-003)
  - EMAIL: requires SUMMARY, DESCRIPTION, ATTENDEE (error ICAL-ALARM-004)
  - AUDIO: optional ATTACH
- [ ] Support REPEAT and DURATION for repeated alarms

**Dependencies:** Task 5.7

**Test Requirements:**
```php
// Tests must pass:
testVAlarmRequiresAction()
testVAlarmRequiresTrigger()
testVAlarmDisplayRequiresDescription()
testVAlarmEmailRequiresProperties()
testVAlarmAudioSupportsAttach()
testVAlarmSupportsRepeat()
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

**Description:** Create RRuleParser that parses RRULE strings into structured objects.

**Why Important:** RRULE strings define complex recurring patterns. Accurate parsing is critical.

**Acceptance Criteria:**
- [ ] Parse FREQ component (SECONDLY to YEARLY)
- [ ] Parse INTERVAL modifier
- [ ] Parse UNTIL and COUNT termination conditions
- [ ] Parse all BY* modifiers (BYSECOND, BYMINUTE, BYHOUR, BYDAY, etc.)
- [ ] Parse WKST week start parameter
- [ ] Validate RRULE syntax (error ICAL-RRULE-001 to ICAL-RRULE-005)
- [ ] Handle complex combinations correctly

**Dependencies:** Task 5.8

**Test Requirements:**
```php
// Tests must pass:
testParseFreqDaily()
testParseFreqWeekly()
testParseFreqMonthly()
testParseFreqYearly()
testParseInterval()
testParseUntil()
testParseCount()
testParseByDay()
testParseByMonth()
testParseByMonthDay()
testParseWkst()
testParseComplexRrule()
testParseInvalidRrule()
```

---

### Task 6.2: Recurrence Generator

**Status:** ❌ Not Started  
**Priority:** HIGH  
**Effort**: 16 hours

**Description:** Create RecurrenceGenerator that generates occurrence instances from RRULE patterns.

**Why Important:** Transforms RRULE patterns into actual event dates/times.

**Acceptance Criteria:**
- [ ] Generate instances for all FREQ types
- [ ] Handle timezone-aware generation (error ICAL-RRULE-007)
- [ ] Support EXDATE exceptions
- [ ] Support RDATE additions
- [ ] Implement iterator pattern for memory efficiency
- [ ] Handle edge cases (leap years, DST transitions)
- [ ] Validate against test vectors

**Dependencies:** Task 6.1

**Test Requirements:**
```php
// Tests must pass:
testGenerateDailyInstances()
testGenerateWeeklyInstances()
testGenerateMonthlyInstances()
testGenerateYearlyInstances()
testGenerateWithUntil()
testGenerateWithCount()
testGenerateWithByDay()
testGenerateWithExdate()
testGenerateWithRdate()
testGenerateTimezoneAware()
testGenerateLeapYear()
testGenerateDSTTransition()
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
**Effort**: 20 hours

**Description:** Create tests for all RFC 5545 examples and conformance requirements.

**Why Important:** Verifies RFC compliance.

**Acceptance Criteria:**
- [ ] Test all 14 RFC 5545 examples
- [ ] Parse and round-trip each example
- [ ] Verify output matches specification
- [ ] Test all data types with examples

**Dependencies:** Task 10.1

**Test Requirements:**
```php
// Tests must pass:
testRfcExample1() // through testRfcExample14()
testAllDataTypes()
testRoundTripAllExamples()
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

### Overall Progress: 21% (16/78 tasks complete)

#### Epic Progress:
- Epic 1: Foundation - 100% (3/3 tasks) ✅
- Epic 2: Content Line Processing - 100% (3/3 tasks) ✅
- Epic 3: Property Parsing - 100% (3/3 tasks) ✅
- Epic 4: Data Type Parsers - 100% (4/4 tasks) ✅
- Epic 5: Component System - 38% (3/8 tasks) 🔄
- Epic 6: Recurrence Rules - 0% (0/2 tasks)
- Epic 7: Writer System - 0% (0/4 tasks)
- Epic 8: Main Parser/Writer - 0% (0/2 tasks)
- Epic 9: Validation - 0% (0/2 tasks)
- Epic 10: Test Suite - 0% (0/4 tasks)
- Epic 11: Documentation - 0% (0/3 tasks)

### Next Steps:
1. Proceed to Epic 5: Task 5.4 (VTODO Component)
2. Continue with VJOURNAL and other components

### Blocked Tasks:
None currently - all tasks are ready to start.

### Epic 5 Summary:
Component system in progress:
- ✅ Task 5.1: Component Base Classes (AbstractComponent, ComponentInterface, GenericProperty, TextValue)
- ✅ Task 5.2: VCALENDAR Component
- ✅ Task 5.3: VEVENT Component (with VALARM support)
- ⏳ Task 5.4: VTODO Component
- ⏳ Task 5.5: VJOURNAL Component
- ⏳ Task 5.6: VFREEBUSY Component
- ⏳ Task 5.7: VTIMEZONE Component
- ⏳ Task 5.8: VALARM Component

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
Follow the epic order as listed. Each epic builds on the previous ones. Do not skip ahead unless explicitly noted.

### Test-Driven Development:
- Write tests before implementation for each task
- Ensure all acceptance criteria tests pass
- Maintain 100% test coverage

### Error Handling:
- All error codes from PRD §6 must be implemented
- Use proper exception types with context
- Include line numbers in parse errors

### Performance:
- Keep memory usage low (streaming for large files)
- Cache parsed objects where appropriate
- Use generators for large datasets

### RFC Compliance:
- Follow RFC 5545 exactly
- Test against RFC examples
- Handle edge cases gracefully

---

**Last Updated:** 2026-02-05
**Next Review:** After Epic 3 completion