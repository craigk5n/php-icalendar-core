# Contributing to PHP iCalendar Core

Thank you for your interest in contributing! This guide will help you get started.

## Prerequisites

- PHP 8.2 or higher
- [Composer](https://getcomposer.org/)

## Setup

```bash
git clone https://github.com/craigk5n/php-icalendar-core.git
cd php-icalendar-core
composer install
```

## Development Workflow

1. Fork the repository and create a feature branch from `main`
2. Make your changes
3. Ensure all checks pass (see below)
4. Submit a pull request against `main`

## Running Checks

All of the following must pass before a PR can be merged:

```bash
# Run the test suite
composer test

# Run PHPStan static analysis (level 9 — the maximum)
composer phpstan

# Run Psalm (informational, not yet enforced)
composer psalm
```

## Quality Standards

### Tests

- All new code must include tests
- All existing tests must continue to pass
- The CI enforces an 80% line coverage threshold

### Static Analysis

- **PHPStan level 9** (maximum) is enforced in CI. New code must not introduce PHPStan errors.
- **Psalm level 3** runs in CI but is informational only. Fixing Psalm issues is appreciated but not required.

### Code Style

- Follow existing patterns in the codebase
- Use strict types (`declare(strict_types=1)`) in all PHP files
- Use typed properties, parameters, and return types

### Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) style:

```
feat: Add VTIMEZONE generation from IANA data
fix: Correct EXDATE matching for DATE-only values
docs: Update installation instructions
test: Add coverage for leap-second edge case
```

## CI Pipeline

Pull requests automatically run the following GitHub Actions jobs:

| Job | Description | Required to pass? |
|-----|-------------|-------------------|
| Tests (PHP 8.2, 8.3, 8.4) | Full test suite across PHP versions | Yes |
| Coverage | 80% line coverage threshold | Yes |
| PHPStan | Level 9 static analysis | Yes |
| Psalm | Level 3 static analysis | No (informational) |

## Project Structure

```
src/                  # Library source code
  Component/          # iCalendar components (VEvent, VTodo, etc.)
  Exception/          # Exception classes
  Parser/             # Parsing pipeline
    ValueParser/      # Data type parsers (Date, DateTime, Duration, etc.)
  Recurrence/         # RRULE parsing and instance generation
  Validation/         # Validation system
  Writer/             # Serialization pipeline
    ValueWriter/      # Data type writers
tests/                # PHPUnit test suite
```

## Reporting Issues

Please use [GitHub Issues](https://github.com/craigk5n/php-icalendar-core/issues) to report bugs or request features. When reporting a bug, include:

- PHP version
- Minimal `.ics` input that reproduces the issue
- Expected vs. actual behavior

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
