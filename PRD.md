# Product Requirements Document (PRD) - PHP iCalendar Core

## 1. Introduction

### 1.1 Goal

PHP iCalendar Core aims to provide developers with a reliable, efficient, and flexible tool for parsing and writing iCalendar (RFC 5545) data. It should handle complex iCalendar features like recurrence rules and timezones accurately, while also offering adaptability for real-world data that may not be perfectly compliant, and support standardized extensions for richer data representation.

### 1.2 Scope

This document outlines the requirements for the library, focusing on its core parsing and writing functionalities, component/property support, recurrence handling, the implementation of distinct **strict** and **lenient** parsing modes, and support for standardized extensions like RFC 9073.

## 2. Goals

-   **Robust Parsing:** Accurately parse iCalendar data into structured PHP objects.
-   **Reliable Writing:** Generate valid RFC 5545 iCalendar data from PHP objects.
-   **Comprehensive Feature Support:** Handle core iCalendar components, properties, recurrence rules (RRULE, EXDATE, RDATE), and timezone data.
-   **Flexible Parsing Modes:** Offer both strict and lenient parsing options to accommodate data quality variations.
-   **Standardized Extension Support:** Implement support for relevant RFCs that extend iCalendar capabilities, specifically RFC 9073 for rich text descriptions (`STYLED-DESCRIPTION`).
-   **Performance:** Maintain efficiency, particularly for large iCalendar files. Performance requirements are calibrated for execution **without** code coverage enabled.
-   **Extensibility:** Allow for future expansion with custom components or value types.

## 3. User Stories

### 3.1 Parsing Modes

*   **As a developer importing potentially non-compliant `.ics` files, I want to use a lenient parsing mode so that the library attempts to import as much data as possible, collecting warnings for issues rather than failing outright.**
*   **As a developer validating `.ics` files for strict RFC 5545 compliance, I want to use a strict parsing mode so that the library acts as a syntax checker and throws an exception for any deviation from the standard.**

### 3.2 Rich Text Descriptions (RFC 9073)

*   **As a developer parsing iCalendar data, I want the library to correctly parse and store `STYLED-DESCRIPTION` properties, preserving their rich text content (e.g., HTML) or URI references.** This includes handling `VALUE=TEXT` and `VALUE=URI` types.
*   **As a developer parsing iCalendar data, I want the library to correctly handle the backward compatibility rule for `DESCRIPTION` when `STYLED-DESCRIPTION` is present:** The library should ignore plain `DESCRIPTION` properties unless they are marked `DERIVED=TRUE`.
*   **As a developer generating iCalendar data, I want the library to correctly write `STYLED-DESCRIPTION` properties, including their rich text content or URI references.**
*   **As a developer generating iCalendar data, I want the library to manage the `DESCRIPTION` property according to RFC 9073 rules when `STYLED-DESCRIPTION` is present** (e.g., omitting plain `DESCRIPTION` or preserving `DERIVED=TRUE`).

## 4. Functional Requirements

### 4.1 Parser

#### 4.1.1 Parsing Modes

The `Parser` class MUST support two distinct parsing modes: `STRICT` and `LENIENT`.
*   **Mode Selection:** Clients will select the mode by passing a constant (`Parser::STRICT` or `Parser::LENIENT`) to the `Parser` constructor. The default mode MUST be `Parser::STRICT`.
*   **Strict Mode Behavior:**
    *   In strict mode, the parser MUST throw a `ParseException` immediately upon encountering any violation of RFC 5545 syntax or data format.
    *   This mode should function as a comprehensive syntax checker for iCalendar files.
*   **Lenient Mode Behavior:**
    *   In lenient mode, the parser MUST attempt to parse the `.ics` data as fully as possible.
    *   For specific violations related to **dates**, **times**, and the **`SUMMARY` property**, the parser MUST NOT throw an exception. Instead, it MUST collect a descriptive warning message.
    *   A new method, `getWarnings()`, MUST be added to the `Parser` class to retrieve an array of all collected warnings. (`getErrors()` is an alias for backward compatibility).
    *   Other critical parsing errors (e.g., malformed `BEGIN`/`END` markers, unknown components) may still result in exceptions if they prevent basic structure parsing.

#### 4.1.2 STYLED-DESCRIPTION Support (RFC 9073)

*   The parser MUST recognize the `STYLED-DESCRIPTION` property.
*   It MUST correctly parse the value of `STYLED-DESCRIPTION`, handling `VALUE=TEXT` (for inline rich text/HTML) and `VALUE=URI`. The parsed value should preserve the rich text content as a string.
*   During parsing, if `STYLED-DESCRIPTION` is present within a component, any plain `DESCRIPTION` property *without* the `DERIVED=TRUE` parameter MUST be ignored or omitted from the component's final property list, adhering to RFC 9073 backward compatibility rules.
*   `DESCRIPTION` properties with the `DERIVED=TRUE` parameter SHOULD be preserved alongside `STYLED-DESCRIPTION`.

### 4.2 Writer

*   The writer MUST correctly serialize `STYLED-DESCRIPTION` properties, preserving their values (inline rich text and URIs).
*   When writing a component that contains `STYLED-DESCRIPTION`, the writer MUST correctly handle the `DESCRIPTION` property according to RFC 9073 rules: plain `DESCRIPTION` properties (without `DERIVED=TRUE`) MUST be omitted from the output. `DESCRIPTION` properties with `DERIVED=TRUE` SHOULD be preserved.

### 4.3 Documentation

*   The `README.md`, `STATUS.md`, and `PRD.md` files MUST be updated to clearly document:
    *   The existence and purpose of strict and lenient parsing modes.
    *   How to select a mode during parser instantiation.
    *   The behavior differences between the two modes.
    *   Support for RFC 9073 and the `STYLED-DESCRIPTION` property, including its value types, parameters, parsing behavior, and interaction with the `DESCRIPTION` property during both parsing and writing.
    *   How to access collected warnings using `getWarnings()`.

### 4.4 Testing

*   Comprehensive test coverage MUST be provided for:
    *   Strict and lenient parsing modes.
    *   `STYLED-DESCRIPTION` parsing (inline HTML, URIs).
    *   `STYLED-DESCRIPTION` writing.
    *   Backward compatibility logic for `DESCRIPTION` when `STYLED-DESCRIPTION` is present, in both parsing and writing.
    *   Round-trip testing to ensure data integrity.

### 4.5 Standardized Extensions (New)

#### 4.5.1 RFC 7986: New Properties
*   The library MUST support the following properties: `IMAGE`, `COLOR`, `CONFERENCE`, and `REFRESH-INTERVAL`.
*   `IMAGE` MUST support both `VALUE=URI` and `VALUE=BINARY`.
*   `COLOR` MUST support CSS3 color names and hex codes as `TEXT`.
*   `CONFERENCE` MUST be parsed as a `URI`.
*   `REFRESH-INTERVAL` MUST be parsed as a `DURATION`.

#### 4.5.2 RFC 7953: Calendar Availability
*   The library MUST support the `VAVAILABILITY` component and its sub-component `AVAILABLE`.
*   Recurrence rules within `AVAILABLE` MUST be correctly handled to calculate free/busy time.

#### 4.5.3 RFC 9073: Participant Support
*   The library MUST support the `PARTICIPANT` component.
*   This includes parsing and writing participant metadata (Role, URI, etc.) as a modern alternative to `ATTENDEE`.

#### 4.5.4 RFC 7265: jCal (JSON Format)
*   The library SHOULD provide a mechanism to export parsed iCalendar objects into the standard jCal JSON format.

#### 4.5.5 Common De Facto Extensions
*   The library SHOULD support widely used non-standard properties: `X-WR-CALNAME`, `X-WR-TIMEZONE`, and `X-APPLE-STRUCTURED-LOCATION`.

## 5. Design Considerations

*   **Mode Persistence:** The selected parsing mode should be a property of the `Parser` instance and consistently applied.
*   **Error Reporting:** Warnings collected in lenient mode should be informative (code, message, property, line number).
*   **`STYLED-DESCRIPTION` Value Handling:** The library should preserve the raw rich text (e.g., HTML) or URI as a string value for `STYLED-DESCRIPTION`. Specific HTML parsing or rendering is outside the scope of this library.
*   **Conflict Resolution:** Parsing and writing logic must correctly resolve conflicts between `DESCRIPTION` and `STYLED-DESCRIPTION` based on RFC 9073 rules.

## 6. Future Considerations

*   Performance optimizations for extremely large iCalendar files.
*   Expanding lenient mode warning collection to other properties.
*   Configuration options for lenient mode error handling.
*   Potential support for other iCalendar extensions.
	
The `STYLED-DESCRIPTION` property is a significant addition that extends the capabilities beyond basic RFC 5545. It's crucial to ensure its parsing and writing are handled correctly, especially concerning backward compatibility with the `DESCRIPTION` property. This also means considering how the library interacts with rich text content without attempting to interpret it, simply storing and outputting it as per the RFC.