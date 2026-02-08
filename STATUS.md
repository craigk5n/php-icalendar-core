# Status

This document outlines the current development status of PHP iCalendar Core.

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

-   PHP 8.2+
-   No external production dependencies.

## Current Development Focus

-   **Mode Implementation:** Strict and lenient modes are fully implemented.
-   **RFC 9073 Support:** `STYLED-DESCRIPTION` property is now fully parsed and written, with backward compatibility for `DESCRIPTION` handled correctly in both parsing and writing.
-   **Test Coverage:** All implemented features are covered by unit tests.
-   **Documentation:** Detailed documentation for modes and `STYLED-DESCRIPTION` is included in `README.md`, `STATUS.md`, and `PRD.md`.
-   **Recurrence Expansion:** In progress — see Epic RE below.

---

## Epic RE: RRULE Date Expansion API

**Goal:** Provide a high-level API to expand RRULE/EXDATE/RDATE on components into concrete occurrence dates, bridging the existing low-level `RecurrenceGenerator` with the component layer.

**Background:** The library already has a fully implemented `RecurrenceGenerator` (920 lines, `src/Recurrence/RecurrenceGenerator.php`) that expands parsed `RRule` objects into `DateTimeImmutable` streams. However, there is no way to go from a component (e.g., `VEvent`) with raw string properties to "give me the occurrence dates." Components store RRULE as raw strings and have no EXDATE/RDATE getter/setter methods.

**Design decisions:**
- Return type: `Generator<Occurrence>` with `toArray()` convenience
- API: Standalone `RecurrenceExpander` service + `getOccurrences()` convenience on components
- Range bounds: Optional `rangeEnd`, required only for unbounded rules (no COUNT, no UNTIL)
- Multi-RRULE: Supported (union results, merge-sorted, EXDATE subtracted)
- RFC 5545 algorithm: EXDATE applied after RRULE COUNT (not during)

**PRD reference:** §4.6

### Epic RE-1: Occurrence Value Object

> Create an immutable value object representing a single occurrence in the recurrence set.

#### RE-1.1: Create `Occurrence` class

- **Status:** Not Started
- **File:** `src/Recurrence/Occurrence.php` (CREATE)
- **Description:** Create an immutable PHP 8.2 `readonly class` in the `Icalendar\Recurrence` namespace representing a single occurrence in a recurrence set. Follow the same pattern as the existing `RRule` class (`src/Recurrence/RRule.php`).
- **Acceptance Criteria:**
  - [ ] Class is declared as `readonly class Occurrence` in namespace `Icalendar\Recurrence`
  - [ ] Constructor accepts three parameters: `DateTimeImmutable $start`, `?DateTimeImmutable $end = null`, `bool $isRdate = false`
  - [ ] All three constructor parameters are `private readonly`
  - [ ] `getStart()` returns the `DateTimeImmutable` start value
  - [ ] `getEnd()` returns the nullable `DateTimeImmutable` end value
  - [ ] `isRdate()` returns the boolean flag
  - [ ] Class has `declare(strict_types=1)` at the top
  - [ ] File follows PSR-4 autoloading (namespace matches directory structure)

#### RE-1.2: Unit tests for `Occurrence`

- **Status:** Not Started
- **File:** `tests/Recurrence/OccurrenceTest.php` (CREATE)
- **Depends on:** RE-1.1
- **Description:** Write PHPUnit tests for the `Occurrence` value object.
- **Acceptance Criteria:**
  - [ ] Test construction with only `$start` — `getEnd()` returns `null`, `isRdate()` returns `false`
  - [ ] Test construction with `$start` and `$end` — both `getStart()` and `getEnd()` return correct values
  - [ ] Test construction with `$isRdate = true` — `isRdate()` returns `true`
  - [ ] Test that `getStart()` returns the exact same `DateTimeImmutable` instance passed to constructor
  - [ ] All tests pass with `vendor/bin/phpunit tests/Recurrence/OccurrenceTest.php`

---

### Epic RE-2: RecurrenceExpander Service

> Create the core service that extracts recurrence properties from a component, parses them, and generates the full recurrence set per RFC 5545.

#### RE-2.1: Create `RecurrenceExpander` class with property extraction

- **Status:** Not Started
- **File:** `src/Recurrence/RecurrenceExpander.php` (CREATE)
- **Depends on:** RE-1.1
- **Description:** Create the `RecurrenceExpander` class in namespace `Icalendar\Recurrence`. Implement the constructor and private helper methods for extracting and parsing component properties. The constructor accepts an optional `RecurrenceGenerator` (creates one if not provided) and internally instantiates `DateTimeParser`, `DateParser`, `DurationParser`, and `RRuleParser`.
- **Helper methods to implement:**
  - `parseDtStart(ComponentInterface): DateTimeImmutable` — reads `DTSTART` property, checks `VALUE` parameter (`DATE` vs `DATE-TIME`), passes `TZID` parameter to the appropriate parser. Throws `InvalidArgumentException` if `DTSTART` is missing.
  - `parseDuration(ComponentInterface, DateTimeImmutable): ?DateInterval` — checks `DTEND` first (compute interval via `$dtstart->diff($dtend)`), then `DURATION` (parse via `DurationParser`), then `DUE` for VTodo (compute interval). Returns `null` if none found.
  - `parseRrules(ComponentInterface): RRule[]` — calls `getAllProperties('RRULE')`, parses each raw value with `RRuleParser`.
  - `parseExdates(ComponentInterface): DateTimeImmutable[]` — calls `getAllProperties('EXDATE')`, splits each value on commas, checks `VALUE` parameter, parses with appropriate parser. Returns flat array.
  - `parseRdates(ComponentInterface): DateTimeImmutable[]` — same pattern as `parseExdates` but for `RDATE` properties.
- **Acceptance Criteria:**
  - [ ] Class is in namespace `Icalendar\Recurrence` with `declare(strict_types=1)`
  - [ ] Constructor accepts `?RecurrenceGenerator $generator = null` and creates one if null
  - [ ] Constructor internally creates `DateTimeParser`, `DateParser`, `DurationParser`, `RRuleParser` instances
  - [ ] `parseDtStart()` correctly handles `VALUE=DATE` properties (uses `DateParser`)
  - [ ] `parseDtStart()` correctly handles `DATE-TIME` properties with `TZID` parameter
  - [ ] `parseDtStart()` throws `InvalidArgumentException` when `DTSTART` is missing
  - [ ] `parseDuration()` returns `DateInterval` from `DTEND`, `DURATION`, or `DUE` (checked in that order)
  - [ ] `parseDuration()` returns `null` when no end/duration property exists
  - [ ] `parseRrules()` returns `RRule[]` from all `RRULE` properties on the component
  - [ ] `parseExdates()` handles comma-separated values within a single `EXDATE` property
  - [ ] `parseExdates()` handles multiple separate `EXDATE` properties
  - [ ] `parseExdates()` respects `VALUE=DATE` parameter
  - [ ] `parseRdates()` follows the same parsing logic as `parseExdates`

#### RE-2.2: Implement `expand()` method — single RRULE path

- **Status:** Not Started
- **File:** `src/Recurrence/RecurrenceExpander.php` (MODIFY)
- **Depends on:** RE-2.1
- **Description:** Implement the public `expand()` method for the common single-RRULE case. This is the main public API of the class.
- **Algorithm:**
  1. Call `parseDtStart()`, `parseDuration()`, `parseRrules()`, `parseExdates()`, `parseRdates()`
  2. Validate bounds: if any RRULE has no COUNT and no UNTIL and `$rangeEnd` is null, throw `InvalidArgumentException`
  3. Build EXDATE hashset (key: `Y-m-d\TH:i:s`; for `VALUE=DATE` EXDATEs, also key by `Y-m-d`)
  4. Call `RecurrenceGenerator::generate($rule, $dtstart, $rangeEnd, [], [])` — pass empty exdates/rdates arrays (EXDATE is applied at this level per RFC 5545)
  5. Merge sorted RDATEs into the RRULE stream (mark `isRdate=true` on RDATE occurrences)
  6. Filter out EXDATEs
  7. Wrap each surviving date in an `Occurrence` object with computed end time
  8. Handle the no-RRULE case: recurrence set = `{DTSTART} + RDATEs - EXDATEs`
- **Also implement:** `expandToArray()` as `iterator_to_array($this->expand(...), false)`
- **Acceptance Criteria:**
  - [ ] `expand()` signature: `public function expand(ComponentInterface $component, ?DateTimeInterface $rangeEnd = null): Generator`
  - [ ] `expandToArray()` signature: `public function expandToArray(ComponentInterface $component, ?DateTimeInterface $rangeEnd = null): array`
  - [ ] Generator yields `Occurrence` objects (not raw `DateTimeImmutable`)
  - [ ] Each `Occurrence` has correct `start` from the RRULE expansion
  - [ ] Each `Occurrence` has correct `end` computed from DTEND/DURATION/DUE (or null)
  - [ ] EXDATE dates are excluded from the output
  - [ ] EXDATE is applied *after* RRULE COUNT (COUNT=5 with 1 EXDATE = 4 results, not 5)
  - [ ] EXDATE with `VALUE=DATE` excludes all occurrences on that calendar date
  - [ ] RDATE dates appear in the output with `isRdate() === true`
  - [ ] RDATE that coincides with an RRULE date is deduplicated (appears once, not twice)
  - [ ] All output is in chronological order
  - [ ] Throws `InvalidArgumentException` when rule is unbounded and no `$rangeEnd`
  - [ ] Does NOT throw when rule has `COUNT` or `UNTIL` and no `$rangeEnd`
  - [ ] No-RRULE case: yields `{DTSTART} + RDATEs - EXDATEs`
  - [ ] `expandToArray()` returns `Occurrence[]` with integer keys (not preserving generator keys)
  - [ ] Generator calls `RecurrenceGenerator::generate()` with empty `$exdates` and `$rdates` arrays

#### RE-2.3: Implement multi-RRULE merge-sort

- **Status:** Not Started
- **File:** `src/Recurrence/RecurrenceExpander.php` (MODIFY)
- **Depends on:** RE-2.2
- **Description:** Add support for multiple `RRULE` properties on a single component. RFC 5545 allows this (though rare in practice). When multiple RRULEs exist, the expander must call `RecurrenceGenerator::generate()` for each RRULE, then merge-sort the results chronologically and deduplicate.
- **Implementation:** Create a private `mergeSortedGenerators(Generator[] $generators): Generator` method that uses an array-based priority queue:
  1. Read the first value from each generator into a buffer array
  2. Sort the buffer by timestamp
  3. Yield the smallest, refill from that generator
  4. Skip duplicates (same timestamp as previous yield)
  5. Continue until all generators are exhausted
- **Acceptance Criteria:**
  - [ ] `mergeSortedGenerators()` accepts an array of `Generator` objects
  - [ ] Output is sorted chronologically (ascending by timestamp)
  - [ ] Duplicate dates (same timestamp from different RRULEs) appear only once
  - [ ] The `expand()` method detects `count($rrules) > 1` and uses `mergeSortedGenerators()`
  - [ ] For `count($rrules) === 1`, the single generator is used directly (no merge overhead)
  - [ ] A shared EXDATE set is applied to the merged stream (not per-RRULE)
  - [ ] RDATEs are merged into the combined stream after RRULE merge

#### RE-2.4: Unit tests for `RecurrenceExpander`

- **Status:** Not Started
- **File:** `tests/Recurrence/RecurrenceExpanderTest.php` (CREATE)
- **Depends on:** RE-2.3
- **Description:** Comprehensive test coverage for the `RecurrenceExpander` service. Build test components programmatically using `VEvent`, `VTodo`, and `VJournal` with their setter methods. Use `iterator_to_array()` on the generator to collect results for assertions. Follow existing test patterns in `tests/Recurrence/RecurrenceGeneratorTest.php`.
- **Test cases to implement:**

  **Single RRULE:**
  - `testExpandDailyCount` — VEvent with `DTSTART=20260101T090000`, `RRULE FREQ=DAILY;COUNT=5`. Assert 5 occurrences, verify dates are Jan 1-5.
  - `testExpandWeeklyWithDtEnd` — VEvent with DTSTART, DTEND (1 hour later), `RRULE FREQ=WEEKLY;COUNT=3`. Assert each `Occurrence::getEnd()` is 1 hour after start.
  - `testExpandMonthlyWithDuration` — VEvent with DTSTART, `DURATION=PT30M`, RRULE monthly. Assert `getEnd()` = start + 30 minutes.
  - `testExpandYearlyWithUntil` — VEvent with `RRULE FREQ=YEARLY;UNTIL=20280101`. Assert stops at UNTIL.

  **EXDATE:**
  - `testExdateExcludesOccurrence` — Daily COUNT=5, EXDATE on day 3. Assert 4 occurrences, day 3 absent.
  - `testExdateAppliedAfterCount` — Daily COUNT=5, EXDATE on day 3. Assert exactly 4 results (not 5 — EXDATE does NOT cause the RRULE to extend).
  - `testExdateDateOnlyMatching` — All-day event (VALUE=DATE DTSTART), EXDATE with VALUE=DATE. Assert the date is excluded.
  - `testExdateCommaSeparated` — Single EXDATE property with two comma-separated dates. Assert both are excluded.
  - `testExdateMultipleProperties` — Two separate EXDATE properties. Assert both are excluded.
  - `testExdateOnDtstart` — EXDATE matching DTSTART itself. Assert DTSTART is excluded from results.

  **RDATE:**
  - `testRdateAddsOccurrence` — Daily COUNT=3, RDATE on day 5. Assert 4 occurrences, day 5 has `isRdate() === true`.
  - `testRdateDeduplicatesWithRrule` — RDATE that matches an RRULE date. Assert only 1 occurrence at that time (not 2).
  - `testRdateOnlyNoRrule` — Component with DTSTART and RDATE but no RRULE. Assert yields DTSTART + RDATE.

  **Multi-RRULE:**
  - `testMultiRruleUnion` — VEvent with two RRULEs (e.g., `FREQ=WEEKLY;BYDAY=MO;COUNT=3` and `FREQ=WEEKLY;BYDAY=WE;COUNT=3`). Assert union of both, sorted, deduplicated.
  - `testMultiRruleSharedExdate` — Two RRULEs with a shared EXDATE. Assert the EXDATE removes the date from the combined set.

  **Range bounds:**
  - `testUnboundedWithoutRangeEndThrows` — RRULE with no COUNT, no UNTIL, no rangeEnd. Assert `InvalidArgumentException`.
  - `testUnboundedWithRangeEndWorks` — Same rule but with rangeEnd. Assert no exception, results stop at rangeEnd.
  - `testCountBoundedWithoutRangeEnd` — RRULE with COUNT, no rangeEnd. Assert no exception.

  **Edge cases:**
  - `testNoRruleNoRdate` — Component with only DTSTART, no RRULE, no RDATE. Assert yields single `Occurrence` with that DTSTART.
  - `testVjournalEndIsNull` — VJournal with RRULE. Assert `Occurrence::getEnd()` is `null` on every occurrence.
  - `testVtodoWithDue` — VTodo with DTSTART and DUE. Assert `Occurrence::getEnd()` is computed from DTSTART-to-DUE interval.
  - `testExpandToArray` — Verify `expandToArray()` returns a plain `Occurrence[]` array.

- **Acceptance Criteria:**
  - [ ] All test cases listed above are implemented and pass
  - [ ] Tests use programmatic component construction (not parsing raw iCalendar strings)
  - [ ] Tests follow existing PHPUnit patterns in the project (method naming, assertions)
  - [ ] `vendor/bin/phpunit tests/Recurrence/RecurrenceExpanderTest.php` passes with 0 failures, 0 errors

---

### Epic RE-3: Component Integration

> Add EXDATE/RDATE/RRULE methods to components and wire up the convenience `getOccurrences()` API via a shared trait.

#### RE-3.1: Add `setRrule` / `getRrule` to VTodo

- **Status:** Not Started
- **File:** `src/Component/VTodo.php` (MODIFY)
- **Description:** VTodo currently has no RRULE methods, unlike VEvent and VJournal. Add `setRrule(string): self` and `getRrule(): ?string` following the exact same pattern as `VEvent` (see `src/Component/VEvent.php` lines 183-202).
- **Reference pattern (from VEvent):**
  ```php
  public function setRrule(string $rrule): self
  {
      $this->removeProperty('RRULE');
      $this->addProperty(GenericProperty::create('RRULE', $rrule));
      return $this;
  }

  public function getRrule(): ?string
  {
      $prop = $this->getProperty('RRULE');
      if ($prop === null) {
          return null;
      }
      return $prop->getValue()->getRawValue();
  }
  ```
- **Acceptance Criteria:**
  - [ ] `VTodo::setRrule(string $rrule)` exists and returns `self` for method chaining
  - [ ] `setRrule()` removes any existing RRULE before adding the new one (single-value semantics)
  - [ ] `VTodo::getRrule()` returns `?string` — the raw RRULE string or `null`
  - [ ] Round-trip works: `$todo->setRrule('FREQ=DAILY;COUNT=3')->getRrule()` returns `'FREQ=DAILY;COUNT=3'`
  - [ ] Method bodies are identical to the VEvent pattern (use `GenericProperty::create()`)

#### RE-3.2: Create `RecurrenceTrait`

- **Status:** Not Started
- **File:** `src/Component/Traits/RecurrenceTrait.php` (CREATE)
- **Depends on:** RE-2.2
- **Description:** Create a shared trait in the `Icalendar\Component\Traits` namespace providing EXDATE/RDATE property management and `getOccurrences()` convenience. This trait follows the existing pattern of `UtcOffsetFormatterTrait.php` in the same directory. Classes using this trait must extend `AbstractComponent` (which provides `addProperty`, `removeProperty`, `getProperty`, `getAllProperties`).
- **Methods to implement:**

  | Method | Behavior |
  |--------|----------|
  | `addExdate(string $exdate, array $params = []): self` | Creates a `GenericProperty` with name `EXDATE` and adds it via `addProperty()`. Does NOT remove existing EXDATEs (accumulates). `$params` allows `['VALUE' => 'DATE']` or `['TZID' => '...']`. |
  | `setExdate(string $exdate, array $params = []): self` | Calls `removeProperty('EXDATE')` then `addExdate()`. Replaces all existing EXDATEs. |
  | `getExdates(): string[]` | Calls `getAllProperties('EXDATE')`, maps each to `->getValue()->getRawValue()`. Returns `string[]`. |
  | `addRdate(string $rdate, array $params = []): self` | Same pattern as `addExdate` but for `RDATE`. |
  | `setRdate(string $rdate, array $params = []): self` | Same pattern as `setExdate` but for `RDATE`. |
  | `getRdates(): string[]` | Same pattern as `getExdates` but for `RDATE`. |
  | `getOccurrences(?DateTimeInterface $rangeEnd = null): Generator` | Creates a new `RecurrenceExpander()`, calls `$expander->expand($this, $rangeEnd)`, yields from it. |
  | `getOccurrencesArray(?DateTimeInterface $rangeEnd = null): array` | Returns `iterator_to_array($this->getOccurrences($rangeEnd), false)`. |

- **Acceptance Criteria:**
  - [ ] Trait is in file `src/Component/Traits/RecurrenceTrait.php`
  - [ ] Namespace is `Icalendar\Component\Traits`
  - [ ] File has `declare(strict_types=1)`
  - [ ] All 8 methods listed above are implemented
  - [ ] `addExdate()` / `addRdate()` use `new GenericProperty('EXDATE', new TextValue($exdate), $params)` (or equivalent via `GenericProperty::create()` plus parameter setting)
  - [ ] `addExdate()` does NOT call `removeProperty()` — it accumulates
  - [ ] `setExdate()` DOES call `removeProperty('EXDATE')` before adding
  - [ ] `getExdates()` returns `string[]` (raw iCalendar values, not parsed objects)
  - [ ] `getOccurrences()` creates a fresh `RecurrenceExpander` each call (no caching)
  - [ ] `getOccurrences()` uses `yield from` to delegate to the expander's generator
  - [ ] No method name conflicts with existing component methods (`setRrule`/`getRrule` are NOT in the trait)

#### RE-3.3: Apply `RecurrenceTrait` to VEvent, VTodo, VJournal

- **Status:** Not Started
- **Files:** `src/Component/VEvent.php`, `src/Component/VTodo.php`, `src/Component/VJournal.php` (MODIFY)
- **Depends on:** RE-3.1, RE-3.2
- **Description:** Add `use RecurrenceTrait;` to each of the three component classes. Add the import statement `use Icalendar\Component\Traits\RecurrenceTrait;` at the top of each file.
- **Acceptance Criteria:**
  - [ ] `VEvent` class has `use RecurrenceTrait;` inside the class body
  - [ ] `VTodo` class has `use RecurrenceTrait;` inside the class body
  - [ ] `VJournal` class has `use RecurrenceTrait;` inside the class body
  - [ ] Each file has the `use Icalendar\Component\Traits\RecurrenceTrait;` import statement
  - [ ] No method conflicts — existing `setRrule`/`getRrule` on each component are not duplicated by the trait
  - [ ] `composer phpstan` passes at level 9 with no new errors
  - [ ] All existing tests still pass (`composer test`)

#### RE-3.4: Unit tests for `RecurrenceTrait` on components

- **Status:** Not Started
- **File:** `tests/Component/RecurrenceTraitTest.php` (CREATE)
- **Depends on:** RE-3.3
- **Description:** Integration tests verifying the trait methods work correctly when used on actual component classes. Build components programmatically using their setter methods.
- **Test cases:**
  - `testAddExdateAccumulates` — Call `addExdate()` twice on a VEvent, assert `getExdates()` returns array with 2 values.
  - `testSetExdateReplacesAll` — Call `addExdate()` twice, then `setExdate()` once, assert `getExdates()` returns array with 1 value.
  - `testGetExdatesEmpty` — New VEvent with no EXDATEs, assert `getExdates()` returns `[]`.
  - `testAddRdateAccumulates` — Same pattern as EXDATE tests but for RDATE.
  - `testSetRdateReplacesAll` — Same pattern.
  - `testGetRdatesEmpty` — Same pattern.
  - `testExdateWithParameters` — Call `addExdate('20260115', ['VALUE' => 'DATE'])`, verify the property has the VALUE parameter.
  - `testVtodoSetRruleGetRrule` — VTodo `setRrule`/`getRrule` round-trip.
  - `testVtodoGetRruleReturnsNullWhenNotSet` — New VTodo, `getRrule()` returns null.
  - `testGetOccurrencesIntegration` — VEvent with DTSTART, RRULE (`FREQ=DAILY;COUNT=3`), one EXDATE. Call `getOccurrences()`, collect via `iterator_to_array()`, assert correct count and dates.
  - `testGetOccurrencesArrayReturnsArray` — Same setup, call `getOccurrencesArray()`, assert `is_array()` and correct count.
  - `testVtodoGetOccurrencesWithDue` — VTodo with DTSTART, DUE, RRULE. Assert `getEnd()` is computed correctly.
  - `testVjournalGetOccurrencesEndIsNull` — VJournal with DTSTART, RRULE. Assert every `Occurrence::getEnd()` is null.
- **Acceptance Criteria:**
  - [ ] All test cases listed above are implemented and pass
  - [ ] Tests cover VEvent, VTodo, and VJournal
  - [ ] Tests verify round-trip property storage (set then get)
  - [ ] Tests verify `getOccurrences()` integration end-to-end
  - [ ] `vendor/bin/phpunit tests/Component/RecurrenceTraitTest.php` passes with 0 failures, 0 errors

---

### Epic RE-4: Verification & Quality

> Ensure all code passes static analysis and the full test suite.

#### RE-4.1: Full test suite and static analysis pass

- **Status:** Not Started
- **Depends on:** RE-2.4, RE-3.4
- **Description:** Run the full test suite and static analysis to verify nothing is broken and all new code meets quality standards.
- **Acceptance Criteria:**
  - [ ] `composer test` passes with 0 failures, 0 errors
  - [ ] `composer phpstan` passes at level 9 with no new errors
  - [ ] No new test warnings introduced (existing environment warnings are acceptable)
  - [ ] All new files follow PSR-4 autoloading conventions
  - [ ] All new files have `declare(strict_types=1)`

---

### Implementation Order

Tasks should be completed in this order (respecting dependencies):

1. **RE-1.1** — Occurrence class (zero dependencies)
2. **RE-1.2** — Occurrence tests
3. **RE-2.1** — RecurrenceExpander with property extraction helpers
4. **RE-2.2** — RecurrenceExpander `expand()` method (single RRULE)
5. **RE-2.3** — Multi-RRULE merge-sort
6. **RE-3.1** — VTodo `setRrule`/`getRrule` (can be done in parallel with RE-2.x)
7. **RE-2.4** — RecurrenceExpander tests
8. **RE-3.2** — RecurrenceTrait
9. **RE-3.3** — Apply trait to components
10. **RE-3.4** — RecurrenceTrait tests
11. **RE-4.1** — Full verification

### Files Summary

| Action | File | Task |
|--------|------|------|
| CREATE | `src/Recurrence/Occurrence.php` | RE-1.1 |
| CREATE | `src/Recurrence/RecurrenceExpander.php` | RE-2.1, RE-2.2, RE-2.3 |
| CREATE | `src/Component/Traits/RecurrenceTrait.php` | RE-3.2 |
| MODIFY | `src/Component/VTodo.php` | RE-3.1, RE-3.3 |
| MODIFY | `src/Component/VEvent.php` | RE-3.3 |
| MODIFY | `src/Component/VJournal.php` | RE-3.3 |
| CREATE | `tests/Recurrence/OccurrenceTest.php` | RE-1.2 |
| CREATE | `tests/Recurrence/RecurrenceExpanderTest.php` | RE-2.4 |
| CREATE | `tests/Component/RecurrenceTraitTest.php` | RE-3.4 |

---

## Future Considerations

-   Performance optimizations for extremely large iCalendar files.
-   Expanding lenient mode warning collection to other properties.
-   Potentially adding support for other iCalendar extensions if they become critical.
