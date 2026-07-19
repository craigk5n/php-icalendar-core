# Status

Implementation status of PHP iCalendar Core.

Day-to-day work is tracked in [GitHub Issues](https://github.com/craigk5n/php-icalendar-core/issues); this file records where the library stands overall.

## Core Functionality

-   **Parsing**:
    *   Parsing of iCalendar data into a component tree.
    *   Components: VCALENDAR, VEVENT, VTODO, VJOURNAL, VFREEBUSY, VTIMEZONE (with STANDARD/DAYLIGHT), VALARM, VAVAILABILITY/AVAILABLE, PARTICIPANT.
    *   Recurrence rules (RRULE, EXDATE, RDATE), including expansion to occurrences.
    *   Timezone parsing, with recurring VTIMEZONE observances expanded from their RRULEs.
    *   `Parser::parseFile()` reads in chunks rather than loading the file whole.
    *   **Strict and Lenient Parsing Modes**:
        *   **Strict (`Parser::STRICT`)**: enforces RFC 5545, throwing `ParseException` on violation. Acts as a syntax checker.
        *   **Lenient (`Parser::LENIENT`)**: maximises data import, collecting warnings instead of throwing. Retrieve them with `getWarnings()`. A malformed value is reported and dropped, never replaced with an invented one.
    *   **Rich Text Descriptions (RFC 9073)**: `STYLED-DESCRIPTION`, including the backward-compatibility rule for plain `DESCRIPTION` (omitted unless `DERIVED=TRUE`).
    *   **New Properties (RFC 7986)**: `IMAGE`, `COLOR`, `CONFERENCE`, `REFRESH-INTERVAL`.
    *   **Calendar Availability (RFC 7953)**: `VAVAILABILITY` / `AVAILABLE`.
    *   **Participant Support (RFC 9073)**: `PARTICIPANT`.
    *   **jCal (RFC 7265)**: export via `VCalendar::toJson()`.
    *   **Common Extensions**: `X-WR-CALNAME`, `X-WR-TIMEZONE`.
-   **Writing**:
    *   Serialisation of components, properties, values and parameters back to RFC 5545.
    *   Line folding at 75 octets, CRLF terminators, and RFC 6868 parameter encoding.
    *   Structured values keep their separators literal — `GEO`, and `CATEGORIES` as a genuine multi-value TEXT list.
    *   `Writer::writeValidated()` validates before serialising.
-   **Validation**:
    *   Required properties, mutual exclusivity, value ranges, timezone references and recurrence rules.
    *   Single-occurrence properties are enforced (`ICAL-COMP-006`).
    *   `Validator::STRICT` / `Validator::LENIENT` selects ERROR or WARNING severity for violations that still leave usable data.

## Testing

-   2,312 tests. Unit, integration, RFC-conformance and round-trip fidelity suites.
-   Line coverage ~87%, gated at 80% in CI.
-   Mutation testing via Infection, gated on both total and covered-code MSI. Uncovered code counts against the score.
-   The suite runs under a non-UTC timezone in CI as well as UTC, so host-timezone coupling is caught.
-   PHPStan level 9 and Psalm both clean across `src/` and `tests/`.

## Dependencies

-   PHP 8.1+
-   No external production dependencies.

## Known Gaps

Tracked as issues rather than listed here, so this file cannot drift out of date. Notable at the time of writing: custom *component* registration is unsupported (an unknown `BEGIN:` parses to `GenericComponent`), and a few properties are still mapped to the TEXT default rather than a validating parser.

---

*Historical note: the detailed task breakdown for the RRULE expansion epic — `Occurrence`, `RecurrenceExpander`, `RecurrenceTrait` and their tests — previously occupied most of this file. That work is complete; the specifications remain in git history.*
