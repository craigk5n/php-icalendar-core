# Status

This document outlines the current development status of the PHP iCalendar library.

## Core Functionality

-   **Parsing**:
    *   Implemented robust parsing of iCalendar data.
    *   Support for various components (VCALENDAR, VEVENT, VTODO, etc.).
    *   Advanced handling of recurrence rules (RRULE, EXDATE, RDATE).
    *   Timezone parsing and handling.
    *   **Strict and Lenient Parsing Modes**:
        *   **Strict Mode (`Parser::STRICT`)**: Enforces RFC 5545 compliance, throwing `ParseException` for any violation. Acts as a syntax checker.
        *   **Lenient Mode (`Parser::LENIENT`)**: Maximizes data import by collecting warnings for non-compliance in specific areas (dates, times, `SUMMARY` property) instead of throwing exceptions. Warnings can be retrieved via `getWarnings()`. Clients can choose the mode via a constructor parameter.
    *   **Rich Text Descriptions (RFC 9073)**: Implemented support for the `STYLED-DESCRIPTION` property. This property is parsed and stored, and the library correctly handles backward compatibility with the plain `DESCRIPTION` property during parsing (omitting plain `DESCRIPTION` if `STYLED-DESCRIPTION` is present, unless `DESCRIPTION` is marked `DERIVED=TRUE`).
    *   **New Properties (RFC 7986)**: Implemented support for `IMAGE`, `COLOR`, `CONFERENCE`, and `REFRESH-INTERVAL`.
    *   **Calendar Availability (RFC 7953)**: [Planned] Support for `VAVAILABILITY`.
    *   **Participant Support (RFC 9073)**: [Planned] Support for `PARTICIPANT` component.
    *   **jCal Support (RFC 7265)**: [Planned] Export to JSON format.
    *   **Common Extensions**: [Planned] Support for `X-WR-CALNAME`, `X-WR-TIMEZONE`, etc.
-   **Writing**:
    *   Full support for writing iCalendar data structures back into RFC 5545 compliant strings.
    *   Correct serialization of components, properties, values, and parameters.
    *   **Rich Text Descriptions (RFC 9073) Writing**: `STYLED-DESCRIPTION` properties are correctly serialized. When writing a component with `STYLED-DESCRIPTION`, plain `DESCRIPTION` properties (not `DERIVED=TRUE`) are omitted.

## Testing

-   **Unit Tests**: Comprehensive unit tests are in place for all major components, parsers, and writers.
-   **Test Coverage**:
    *   100% test coverage for core parsing and writing logic.
    *   Specific tests for strict mode exception handling.
    *   Specific tests for lenient mode warning collection (dates, times, `SUMMARY`).
    *   Tests for `STYLED-DESCRIPTION` parsing (HTML, URI) and its interaction with `DESCRIPTION`.
    *   Tests for writing `STYLED-DESCRIPTION` and its backward compatibility.
    *   Round-trip testing to ensure data integrity.
    *   Edge cases and performance scenarios covered.

## Dependencies

-   PHP 8.1+
-   No external production dependencies.

## Current Development Focus

-   **Mode Implementation:** Strict and lenient modes are fully implemented.
-   **RFC 9073 Support:** `STYLED-DESCRIPTION` property is now fully parsed and written, with backward compatibility for `DESCRIPTION` handled correctly in both parsing and writing.
-   **Test Coverage:** All implemented features are covered by unit tests.
-   **Documentation:** Detailed documentation for modes and `STYLED-DESCRIPTION` is included in `README.md`, `STATUS.md`, and `PRD.md`.

## Future Considerations

-   Performance optimizations for extremely large iCalendar files.
-   Expanding lenient mode warning collection to other properties.
-   Potentially adding support for other iCalendar extensions if they become critical.
