# PHP iCalendar Core

[![CI](https://github.com/craigk5n/php-icalendar-core/actions/workflows/ci.yml/badge.svg)](https://github.com/craigk5n/php-icalendar-core/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://www.php.net/)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)

This library provides robust parsing and writing capabilities for the iCalendar (RFC 5545) format. It aims for high compatibility with RFC specifications while offering flexible parsing modes and supporting standardized extensions for richer data.

## Features

-   **RFC 5545 Compliant**: Adheres to the iCalendar standard for data representation.
-   **Parser & Writer**: Full support for reading and writing iCalendar data.
-   **Component & Property Support**: Handles various iCalendar components (VEVENT, VTODO, VCALENDAR, etc.) and properties.
-   **Recurrence Rule (RRULE) Handling**: Advanced support for parsing and generating recurrence rules.
-   **Timezone Support**: Correctly parses and handles timezone information.
-   **Rich Text Descriptions (RFC 9073)**: Supports the `STYLED-DESCRIPTION` property for HTML or URI-based rich text descriptions, enhancing event details. This includes parsing `STYLED-DESCRIPTION` and handling backward compatibility with the plain `DESCRIPTION` property (omitting it unless `DERIVED=TRUE`). Writing also respects this rule.
-   **Extensibility**: Designed to be extensible for custom component or property types.
-   **Memory Efficiency**: Utilizes generators and streaming where possible for handling large files. Performance benchmarks are calibrated for execution without code coverage overhead.

## Strict vs. Lenient Parsing Modes

This library supports two parsing modes to accommodate varying `.ics` file compliance:

### Strict Mode (`Parser::STRICT`)

*   **Behavior**: In strict mode, the parser rigorously enforces RFC 5545 compliance. Any deviation from the standard, such as malformed date/time values, unexpected component types, or syntax errors, will result in a `ParseException` being thrown immediately. This mode also enforces strict handling of extensions like `STYLED-DESCRIPTION` and its interaction with `DESCRIPTION`.
*   **Use Case**: Ideal for validating compliant `.ics` files or when strict adherence to the standard is paramount. It acts as a robust syntax checker for iCalendar data.

### Lenient Mode (`Parser::LENIENT`)

*   **Behavior**: In lenient mode, the parser prioritizes data import by attempting to process `.ics` files even with non-compliant structures.
    *   Instead of throwing exceptions for certain violations (specifically related to **dates**, **times**, and the **`SUMMARY` property**), the parser will collect warnings.
    *   These warnings can be retrieved using the `getWarnings()` method after parsing is complete.
    *   The parser attempts to handle known extensions like `STYLED-DESCRIPTION` gracefully, collecting warnings for any non-critical deviations.
*   **Use Case**: Useful for importing data from `.ics` files that may not perfectly adhere to the RFC, allowing for maximum data recovery.

### Choosing a Mode

Clients can select the parsing mode by passing a constant to the `Parser` constructor:

```php
use Icalendar\Parser\Parser;

// Strict mode (default)
$parserStrict = new Parser(Parser::STRICT); 
$calendarStrict = $parserStrict->parse($icsData);

// Lenient mode
$parserLenient = new Parser(Parser::LENIENT);
$calendarLenient = $parserLenient->parse($icsData);

// Access warnings collected in lenient mode
$warnings = $parserLenient->getWarnings(); // Note: getErrors() is an alias for getWarnings() for backward compatibility
```

## Installation

Requires PHP 8.2+ and no external production dependencies.

```bash
# Assuming you have Composer installed
composer require craigk5n/php-icalendar-core
```

## Usage

### Parsing

```php
use Icalendar\Parser\Parser;
use Icalendar\Exception\ParseException;
use Icalendar\Validation\ErrorSeverity; // For checking warning severity if needed

$icsData = file_get_contents('path/to/your/calendar.ics');

// Use Parser::LENIENT to collect warnings for non-compliant data
$parser = new Parser(Parser::LENIENT); 
try {
    $calendar = $parser->parse($icsData);
    
    // Process the calendar object...
    // e.g., $events = $calendar->getComponents('VEVENT');

    // Check for warnings if in lenient mode
    if ($parser->getMode() === Parser::LENIENT) {
        $warnings = $parser->getWarnings(); // getErrors() is an alias for backward compatibility
        if (!empty($warnings)) {
            echo "Parsing completed with warnings:\n";
            foreach ($warnings as $warning) {
                // Example: check if it's a warning
                if ($warning->getSeverity() === ErrorSeverity::WARNING) {
                    echo "- Warning (Code: {$warning->getCode()}): {$warning->getMessage()} (Property: {$warning->getProperty()}) [Line {$warning->getLineNumber()}]\n";
                } else {
                    echo "- Error (Code: {$warning->getCode()}): {$warning->getMessage()} (Property: {$warning->getProperty()}) [Line {$warning->getLineNumber()}]\n";
                }
            }
        }
    }

} catch (ParseException $e) {
    echo "Parsing failed: " . $e->getMessage() . "\n";
}
```

### `STYLED-DESCRIPTION` Support (RFC 9073)

The library now supports the `STYLED-DESCRIPTION` property introduced in RFC 9073, which allows for rich text descriptions (e.g., HTML) or URI references.

*   **Parsing:** `STYLED-DESCRIPTION` is parsed and stored. When `STYLED-DESCRIPTION` is present, plain `DESCRIPTION` properties that are not marked `DERIVED=TRUE` are ignored during parsing to adhere to RFC 9073's backward compatibility rules.
*   **Writing:** `STYLED-DESCRIPTION` properties are correctly serialized. When writing a component that includes `STYLED-DESCRIPTION`, any plain `DESCRIPTION` properties without `DERIVED=TRUE` will be omitted from the output.

### Writing

```php
use Icalendar\Writer\Writer;
use Icalendar\Component\VCalendar;

// Assume $calendar is a VCalendar object populated with data
$writer = new Writer();
$icsString = $writer->write($calendar);

file_put_contents('output.ics', $icsString);
```

## Contributing

Please refer to the `CONTRIBUTING.md` file.

## AI Assistance Disclosure

This project was developed with the assistance of generative AI tools (Claude Code by Anthropic, OpenCode, and Google Gemini) for tasks such as:

- Structuring the initial PRD and task breakdown
- Generating code skeletons, design patterns, and documentation drafts
- Suggesting test cases and edge-case handling
- Refining explanations, README content, and commit messages

**All AI-generated output has been carefully reviewed, edited, tested, and validated by the human maintainer (Craig Knudsen) for correctness, security, RFC 5545 compliance, and project goals.** No AI-generated code was merged without human understanding and modification.

The final codebase, architecture decisions, and quality assurance remain the responsibility of the human author. This approach accelerated development while preserving full ownership and accountability.

## License

This project is licensed under the MIT License - see the `LICENSE` file for details.
