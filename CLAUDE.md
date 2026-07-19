# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHP iCalendar Core - an RFC 5545 compliant PHP library for parsing and writing iCalendar data with zero external dependencies. Requires PHP >=8.2.

## Commands

```bash
# Install dependencies
composer install

# Run tests
composer test
# or: vendor/bin/phpunit

# Static analysis (level 9)
composer phpstan

# Run specific test file
vendor/bin/phpunit tests/Parser/ParserTest.php
```

## Architecture

### Core Components

**Parser System** (`src/Parser/`)
- `Parser.php` - Main parser orchestrating the parsing pipeline
- `Lexer.php` - Tokenises into content lines; `tokenizeFile()` streams in chunks
- `PropertyParser.php` - Parses property names, parameters, and values
- `ParameterParser.php` - Parses property parameters
- `LineFolder.php` - Handles RFC 5545 line folding/unfolding (75-octet limit)
- `ContentLine.php` - Represents a single parsed content line

**Value Parsers** (`src/Parser/ValueParser/`)
- `ValueParserFactory.php` - Factory for the 16 value types, plus the property/type map
- RFC 5545 §3.3 types: `DateParser`, `DateTimeParser`, `TimeParser`, `DurationParser`, `PeriodParser`, `TextParser`, `BinaryParser`, `BooleanParser`, `IntegerParser`, `FloatParser`, `UriParser`, `CalAddressParser`, `UtcOffsetParser`, `RecurParser`
- Structured types: `GeoParser` (`lat;lon`), `RequestStatusParser` (`statcode;statdesc[;extdata]`)
- `DateValidator.php` - Shared calendar-date range checking

A property absent from the factory's property/type map silently inherits the
TEXT default, and `TextParser` cannot fail — so **an unmapped property is an
unvalidated one**. Add new properties to that map.

**Component System** (`src/Component/`)
- `AbstractComponent.php` - Base class with property/subcomponent management; `validate()` is the template method, components override `validateSelf()`
- `VCalendar`, `VEvent`, `VTodo`, `VJournal`, `VFreeBusy`, `VAlarm`, `VTimezone` (+ `Standard`, `Daylight`), `VAvailability` (+ `Available`), `Participant`

**Shared traits** (`src/Component/Traits/`) — behaviour common to several
components lives here rather than being copied:
- `CategoriesTrait` - CATEGORIES as a multi-value TEXT list (VEvent, VTodo, VJournal)
- `RecurrenceTrait` - EXDATE/RDATE accessors and `getOccurrences()`
- `RequestStatusTrait` - `addRequestStatus()` (VEvent, VTodo, VJournal, VFreeBusy)
- `UrlTrait` - URL accessors
- `UtcOffsetFormatterTrait` - UTC-offset formatting for observances

**Writer System** (`src/Writer/`)
- `Writer.php` - Main writer; `writeValidated()` validates before serialising
- `PropertyWriter.php` - Serializes properties
- `ContentLineWriter.php` - Applies output line folding
- `ValueWriter/` - Per-type writers mirroring the parsers

**Supporting Systems**
- `src/Recurrence/` - RRULE parsing, occurrence generation and expansion
- `src/Timezone/` - Stubs only (`TimezoneResolver`, `TimezoneDatabase` are empty, uncalled); timezone resolution actually lives in `VTimezone`
- `src/Validation/` - Validation with `ErrorSeverity` enum (WARNING, ERROR, FATAL); `Validator::STRICT`/`LENIENT` selects severity
- `src/Exception/` - `ParseException`, `ValidationException`, `InvalidDataException`

### Design Patterns

- Factory pattern for value parsers and writers
- Strategy pattern for different value types
- Template method for component validation (`validate()` → `validateSelf()`)
- Traits for behaviour shared across components
- Fluent interface for component building
- Generators for streaming (`Lexer::tokenizeFile()`, recurrence expansion)

### Structured values

A value whose grammar contains structural delimiters (`GEO`, `REQUEST-STATUS`,
`CATEGORIES`) **must not be serialised as TEXT** — the TEXT writer escapes those
delimiters and destroys the structure. Give it a typed value plus its own
writer, and make any setter store that type rather than
`GenericProperty::create()`, which stores TEXT.

### Error Codes

All errors use the form `ICAL-<CATEGORY>-<NNN>`:
- `ICAL-PARSE-*` - Content-line and property parsing
- `ICAL-TYPE-*` - Data type errors (currently to `-017`)
- `ICAL-COMP-*` - Calendar-level component errors
- `ICAL-SEC-*` - Security limits (depth, schemes, private IPs, XXE)
- `ICAL-IO-*` - File access
- `ICAL-RRULE-*` - Recurrence rules
- `ICAL-TZ-*`, `ICAL-TZ-OBS-*` - Timezones and observances
- `ICAL-VAL-*`, `ICAL-WRITE-*` - Validation and writing
- Per-component: `ICAL-VEVENT-*`, `ICAL-VTODO-*`, `ICAL-VJOURNAL-*`, `ICAL-VFB-*`, `ICAL-ALARM-*`, `ICAL-PART-*`, `ICAL-AVAIL-*`, `ICAL-VAVAIL-*`

Recently added: `ICAL-TYPE-016` (GEO), `ICAL-TYPE-017` (REQUEST-STATUS),
`ICAL-COMP-006` (duplicate single-occurrence property). Note `ICAL-TYPE-015`
belongs to `InvalidDataException`, not `ParseException`.

Codes are append-only: never repurpose a published code, since callers branch
on them. The authoritative list lives with the exceptions that raise them —
`ParseException`, `ValidationException`, `InvalidDataException`. Adding one
means adding a constant there and taking the next free number in its category.

See PRD.md §5 for the scheme.

## Implementation Status

See `STATUS.md`. Deliberately not duplicated here: two copies of a status list
drift apart, and this one had claimed the Lexer was a stub and recurrence
generation unstarted long after both shipped.

## Known Issues

Tracked in GitHub Issues rather than listed here, for the same reason.

The one standing gap worth knowing while working in the tree: `src/Timezone/`
contains two empty, uncalled classes. Timezone resolution is implemented in
`VTimezone` instead.

## Testing Notes

- Two gates beyond PHPUnit: **PHPStan level 9** and **Psalm**, both over `src/`
  *and* `tests/`. `composer psalm` runs the same scope CI does — a narrower
  local invocation has broken PRs before.
- **Infection** gates on total and covered-code MSI, with uncovered code
  counting against the score. Floors sit a few points below the measured value
  because MSI is not reproducible run to run (mutants that time out are dropped
  from the denominator).
- The suite runs under a **non-UTC timezone** in CI as well as UTC. Never write
  a test that depends on the host clock; pin the zone explicitly.
- `failOnWarning`, `failOnRisky` and `failOnIncomplete` are on. `failOnSkipped`
  is deliberately off — the two skips are environmental, not placeholders.

## Key Documentation

- `PRD.md` - Product requirements and RFC compliance details
- `STATUS.md` - Implementation status; day-to-day work is tracked in GitHub Issues
- `docs/USAGE.md` - API usage examples
