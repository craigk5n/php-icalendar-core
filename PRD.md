# Product Requirements Document (PRD) - PHP iCalendar Library

## 1. Introduction

### 1.1 Goal

The PHP iCalendar Library aims to provide developers with a reliable, efficient, and flexible tool for parsing and writing iCalendar (RFC 5545) data. It should handle complex iCalendar features like recurrence rules and timezones accurately, while also offering adaptability for real-world data that may not be perfectly compliant.

### 1.2 Scope

This document outlines the requirements for the library, focusing on its core parsing and writing functionalities, component/property support, recurrence handling, and crucially, the implementation of distinct **strict** and **lenient** parsing modes.

## 2. Goals

-   **Robust Parsing**: Accurately parse iCalendar data into structured PHP objects.
-   **Reliable Writing**: Generate valid RFC 5545 iCalendar data from PHP objects.
-   **Comprehensive Feature Support**: Handle core iCalendar components, properties, recurrence rules (RRULE, EXDATE, RDATE), and timezone data.
-   **Flexible Parsing Modes**: Offer both strict and lenient parsing options to accommodate data quality variations.
-   **Performance**: Maintain efficiency, particularly for large iCalendar files.
-   **Extensibility**: Allow for future expansion with custom components or value types.

## 3. User Stories

### 3.1 Parsing Modes

*   **As a developer importing potentially non-compliant `.ics` files, I want to use a lenient parsing mode so that the library attempts to import as much data as possible, collecting warnings for issues rather than failing outright.**
*   **As a developer validating `.ics` files for strict RFC 5545 compliance, I want to use a strict parsing mode so that the library acts as a syntax checker and throws an exception for any deviation from the standard.**

## 4. Functional Requirements

### 4.1 Parser

#### 4.1.1 Parsing Modes

The `Parser` class MUST support two distinct parsing modes: `STRICT` and `LENIENT`.
*   **Mode Selection**: Clients will select the mode by passing a constant (`Parser::STRICT` or `Parser::LENIENT`) to the `Parser` constructor. The default mode MUST be `Parser::STRICT`.
*   **Strict Mode Behavior**:
    *   In strict mode, the parser MUST throw a `ParseException` immediately upon encountering any violation of RFC 5545 syntax or data format.
    *   This mode should function as a comprehensive syntax checker for iCalendar files.
*   **Lenient Mode Behavior**:
    *   In lenient mode, the parser MUST attempt to parse the `.ics` data as fully as possible.
    *   For specific violations related to **dates**, **times**, and the **`SUMMARY` property**, the parser MUST NOT throw an exception. Instead, it MUST collect a descriptive warning message.
    *   A new method, `getWarnings()`, MUST be added to the `Parser` class to retrieve an array of all collected warnings. (Note: `getErrors()` is an alias for backward compatibility).
    *   Other critical parsing errors (e.g., malformed `BEGIN`/`END` markers, unknown components) MAY still result in exceptions if they prevent basic structure parsing.

#### 4.1.2 Error Handling

*   **Strict Mode**: Exceptions (`ParseException`, `ValidationException`) are thrown for violations.
*   **Lenient Mode**: Warnings are collected and accessible via `getWarnings()`. Critical errors that prevent parsing may still throw exceptions.

### 4.2 Documentation

*   The `README.md`, `STATUS.md`, and `PRD.md` files MUST be updated to clearly document:
    *   The existence and purpose of strict and lenient parsing modes.
    *   How to select a mode during parser instantiation.
    *   The behavior differences between the two modes, including how errors/warnings are handled in each mode.
    *   The types of data (dates, times, `SUMMARY`) that generate warnings in lenient mode.
    *   How to access collected warnings using `getWarnings()`.

### 4.3 Testing

*   Comprehensive test coverage MUST be provided for both strict and lenient modes.
*   Tests MUST verify:
    *   Strict mode throws exceptions for various RFC violations.
    *   Lenient mode collects warnings correctly for invalid dates, times, and `SUMMARY` properties.
    *   Lenient mode does not throw exceptions for these specific violations.
    *   Overall parsing and writing functionality remains correct in both modes.

## 5. Design Considerations

*   **Mode Persistence**: The selected mode should be a property of the `Parser` instance and consistently applied throughout its operation.
*   **Warning Structure**: Warnings collected in lenient mode should be informative, ideally including the error code, a descriptive message, the affected property/component, and the line number.

## 6. Future Considerations

*   Expanding lenient mode to collect warnings for a broader range of iCalendar properties beyond dates, times, and `SUMMARY`.
*   Providing configurable logging for warnings in lenient mode.
