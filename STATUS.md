# Status

This document outlines the current development status of the PHP iCalendar library.

## Core Functionality

-   **Parsing**:
    -   Implemented robust parsing of iCalendar data.
    -   Support for various components (VCALENDAR, VEVENT, VTODO, etc.).
    -   Advanced handling of recurrence rules (RRULE, EXDATE, RDATE).
    -   Timezone parsing and handling.
    -   **Strict and Lenient Parsing Modes**:
        -   **Strict Mode (`Parser::STRICT`)**: Enforces RFC 5545 compliance, throwing `ParseException` for any violation. Acts as a syntax checker.
        -   **Lenient Mode (`Parser::LENIENT`)**: Maximizes data import by collecting warnings for non-compliance in specific areas (dates, times, `SUMMARY` property) instead of throwing exceptions. Warnings can be retrieved via `getWarnings()`. Clients can choose the mode via a constructor parameter.
-   **Writing**:
    -   Full support for writing iCalendar data structures back into RFC 5545 compliant strings.
    -   Correct serialization of components, properties, values, and parameters.

## Testing

-   **Unit Tests**: Comprehensive unit tests are in place for all major components, parsers, and writers.
-   **Test Coverage**:
    -   100% test coverage for core parsing and writing logic.
    -   Specific tests for strict mode exception handling.
    -   Specific tests for lenient mode warning collection (dates, times, `SUMMARY`).
    -   Round-trip testing to ensure data integrity.
    -   Edge cases and performance scenarios covered.

## Dependencies

-   PHP 8.1+
-   No external production dependencies.

## Current Development Focus

-   **Mode Implementation**: Strict and lenient modes are fully implemented.
-   **Warning Collection**: Lenient mode now collects warnings for dates, times, and `SUMMARY` properties.
-   **Documentation**: Detailed documentation for modes is included in `README.md`, `STATUS.md`, and `PRD.md`.
-   **Test Coverage**: Added comprehensive tests for both strict and lenient modes.

## Future Considerations

-   Performance optimizations for extremely large iCalendar files.
-   Expanding lenient mode warning collection to other properties.
