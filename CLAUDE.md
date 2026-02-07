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
- `PropertyParser.php` - Parses property names, parameters, and values
- `ParameterParser.php` - Parses property parameters
- `LineFolder.php` - Handles RFC 5545 line folding/unfolding (75-octet limit)
- `ContentLine.php` - Represents a single parsed content line

**Value Parsers** (`src/Parser/ValueParser/`)
- `ValueParserFactory.php` - Factory for all 15 RFC 5545 data type parsers
- Individual parsers: `DateParser`, `DateTimeParser`, `TimeParser`, `DurationParser`, `PeriodParser`, `TextParser`, `BinaryParser`, `BooleanParser`, `IntegerParser`, `FloatParser`, `UriParser`, `CalAddressParser`, `UtcOffsetParser`, `RecurParser`

**Component System** (`src/Component/`)
- `AbstractComponent.php` - Base class with property/subcomponent management
- Implemented: `VCalendar`, `VEvent`, `VAlarm`
- Stubs: `VTodo`, `VJournal`, `VFreeBusy`, `VTimezone`, `Standard`, `Daylight`

**Writer System** (`src/Writer/`)
- `Writer.php` - Main writer (stub)
- `PropertyWriter.php` - Serializes properties
- `ContentLineWriter.php` - Applies output line folding

**Supporting Systems**
- `src/Recurrence/` - RRULE parsing and instance generation (stubs)
- `src/Timezone/` - Timezone resolution (stubs)
- `src/Validation/` - Validation with `ErrorSeverity` enum (WARNING, ERROR, FATAL)
- `src/Exception/` - `ParseException`, `ValidationException`, `InvalidDataException`

### Design Patterns

- Factory pattern for value parsers
- Strategy pattern for different value types
- Fluent interface for component building
- Generator pattern planned for streaming large files

### Error Codes

All errors use `ICAL-` prefix with categories:
- `ICAL-PARSE-*` - Parser errors
- `ICAL-TYPE-*` - Data type errors
- `ICAL-COMP-*` - Component errors
- `ICAL-RRULE-*` - Recurrence errors

See PRD.md §6 for complete error code reference.

## Implementation Status

Completed: Foundation, Content Line Processing, Property Parsing, all 15 Data Type Parsers, VCalendar/VEvent/VAlarm (basic), Standard/Daylight (basic) components.

In Progress: Remaining components (VTodo, VJournal, VFreeBusy, VTimezone), Lexer, Security hardening.

Not Started: Recurrence generation, Writer system completion, Main parser orchestration, Validation rules.

## Known Issues

| File | Issue |
|------|-------|
| `src/Parser/ValueParser/DurationParser.php:31` | Uses `ICAL-TYPE-020` but should be `ICAL-TYPE-006` per PRD |
| `src/Parser/Lexer.php` | Stub only - needs full implementation |

## Key Documentation

- `PRD.md` - Complete product requirements and RFC compliance details
- `STATUS.md` - Detailed task tracking with parallelization opportunities
- `AGENTS.md` - AI agent operational guidelines
