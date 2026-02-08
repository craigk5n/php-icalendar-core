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

-   **Recurrence Expansion:** In progress — see Epic RE below.
-   **Timezone Handling:** Proper timezone propagation implemented for all occurrence dates.
-   **Error Resilience:** Graceful handling of malformed RRULE properties.
-   **Incremental Validation:** Intermediate testing checkpoints between major implementation steps.
-   **Rollback Strategy:** Clear plan for reverting changes if tests fail mid-implementation.

---

## Epic RE: RRULE Date Expansion API

**Goal:** Provide a high-level API to expand RRULE/EXDATE/RDATE on components into concrete occurrence dates, bridging the existing low-level `RecurrenceGenerator` with the component layer.

**Background:** The library already has a fully implemented `RecurrenceGenerator` (920 lines, `src/Recurrence/RecurrenceGenerator.php`) that expands parsed `RRule` objects into `DateTimeImmutable` streams. However, there is no way to go from a component (e.g., `VEvent`) with raw string properties to "give me the occurrence dates." Components store RRULE as raw strings and have no EXDATE/RDATE getter/setter methods.

**Key Design Decisions:**
- Return type: `Generator<Occurrence>` with `toArray()` convenience
- API: Standalone `RecurrenceExpander` service + `getOccurrences()` convenience on components
- Range bounds: Optional `rangeEnd`, required only for unbounded rules (no COUNT, no UNTIL)
- Multi-RRULE: Supported (union results, merge-sorted, EXDATE subtracted)
- RFC 5545 algorithm: EXDATE applied after RRULE COUNT (not during)
- Timezone handling: Proper timezone propagation for all occurrence dates
- Error resilience: Graceful handling of malformed RRULE properties

**Existing Code References:**
- `src/Recurrence/RecurrenceGenerator.php` - 920-line recurrence expansion engine (already implemented)
- `src/Recurrence/RRule.php` - Pattern for immutable readonly value objects
- `src/Component/VEvent.php:183-202` - Reference pattern for `setRrule`/`getRrule` methods
- `src/Component/Traits/DateTimePropertiesFormatterTrait.php` - Example trait pattern

**PRD reference:** §4.6

---

### Epic RE-1: Occurrence Value Object

> Create an immutable value object representing a single occurrence in the recurrence set.

#### RE-1.1: Create `Occurrence` class

- **Status:** Completed
- **File:** `src/Recurrence/Occurrence.php` (CREATE)
- **Description:** Create an immutable PHP 8.2 `readonly class` in the `Icalendar\Recurrence` namespace representing a single occurrence in a recurrence set. Follow the same pattern as the existing `RRule` class (`src/Recurrence/RRule.php`).
- **Acceptance Criteria:**
  - [x] Class is declared as `readonly class Occurrence` in namespace `Icalendar\Recurrence`
  - [x] Constructor accepts three parameters: `DateTimeImmutable $start`, `?DateTimeImmutable $end = null`, `bool $isRdate = false`
  - [x] All three constructor parameters are `private readonly`
  - [x] `getStart()` returns the `DateTimeImmutable` start value
  - [x] `getEnd()` returns the nullable `DateTimeImmutable` end value
  - [x] `isRdate()` returns the boolean flag
  - [x] Class has `declare(strict_types=1)` at the top
  - [x] File follows PSR-4 autoloading (namespace matches directory structure)

#### RE-1.2: Unit tests for `Occurrence`

- **Status:** Completed
- **File:** `tests/Recurrence/OccurrenceTest.php` (CREATE)
- **Depends on:** RE-1.1
- **Description:** Write PHPUnit tests for the `Occurrence` value object.
- **Acceptance Criteria:**
  - [x] Test construction with only `$start` — `getEnd()` returns `null`, `isRdate()` returns `false`
  - [x] Test construction with `$start` and `$end` — both `getStart()` and `getEnd()` return correct values
  - [x] Test construction with `$isRdate = true` — `isRdate()` returns `true`
  - [x] Test that `getStart()` returns the exact same `DateTimeImmutable` instance passed to constructor
  - [x] All tests pass with `vendor/bin/phpunit tests/Recurrence/OccurrenceTest.php`
- **Validation Checkpoint:** Run `composer test` after RE-1.2 to verify Occurrence class

---

### Epic RE-2: RecurrenceExpander Service

> Create the core service that extracts recurrence properties from a component, parses them, and generates the full recurrence set per RFC 5545.

#### RE-2.1: Create `RecurrenceExpander` class with property extraction

- **Status:** Completed
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
  - [x] Class is in namespace `Icalendar\Recurrence` with `declare(strict_types=1)`
  - [x] Constructor accepts `?RecurrenceGenerator $generator = null` and creates one if null
  - [x] Constructor internally creates `DateTimeParser`, `DateParser`, `DurationParser`, `RRuleParser` instances
  - [x] `parseDtStart()` correctly handles `VALUE=DATE` properties (uses `DateParser`)
  - [x] `parseDtStart()` correctly handles `DATE-TIME` properties with `TZID` parameter
  - [x] `parseDtStart()` throws `InvalidArgumentException` when `DTSTART` is missing
  - [x] `parseDuration()` returns `DateInterval` from `DTEND`, `DURATION`, or `DUE` (checked in that order)
  - [x] `parseDuration()` returns `null` when no end/duration property exists
  - [x] `parseRrules()` returns `RRule[]` from all `RRULE` properties on the component
  - [x] `parseExdates()` handles comma-separated values within a single `EXDATE` property
  - [x] `parseExdates()` handles multiple separate `EXDATE` properties
  - [x] `parseExdates()` respects `VALUE=DATE` parameter
  - [x] `parseRdates()` follows the same parsing logic as `parseExdates`
- **Validation Checkpoint:** Run unit tests after implementing helper methods
- **Rollback Strategy:** If tests fail, revert to previous working state before proceeding

#### RE-2.2: Implement `expand()` method — single RRULE path

- **Status:** Completed
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
  - [x] `expand()` signature: `public function expand(ComponentInterface $component, ?DateTimeInterface $rangeEnd = null): Generator`
  - [x] `expandToArray()` signature: `public function expandToArray(ComponentInterface $component, ?DateTimeInterface $rangeEnd = null): array`
  - [x] Generator yields `Occurrence` objects (not raw `DateTimeImmutable`)
  - [x] Each `Occurrence` has correct `start` from the RRULE expansion
  - [x] Each `Occurrence` has correct `end` computed from DTEND/DURATION/DUE (or null)
  - [x] EXDATE dates are excluded from the output
  - [x] EXDATE is applied *after* RRULE COUNT (COUNT=5 with 1 EXDATE = 4 results, not 5)
  - [x] EXDATE with `VALUE=DATE` excludes all occurrences on that calendar date
  - [x] RDATE dates appear in the output with `isRdate() === true`
  - [x] RDATE that coincides with an RRULE date is deduplicated (appears once, not twice)
  - [x] All output is in chronological order
  - [x] Throws `InvalidArgumentException` when rule is unbounded and no `$rangeEnd`
  - [x] Does NOT throw when rule has `COUNT` or `UNTIL` and no `$rangeEnd`
  - [x] No-RRULE case: yields `{DTSTART} + RDATEs - EXDATEs`
  - [x] `expandToArray()` returns `Occurrence[]` with integer keys (not preserving generator keys)
  - [x] Generator calls `RecurrenceGenerator::generate()` with empty `$exdates` and `$rdates` arrays
- **Intermediate Validation:** Run tests after implementing basic RRULE expansion before adding RDATE/EXDATE logic
- **Validation Checkpoint:** Run unit tests for RE-2.2 to validate single RRULE functionality
- **Rollback Strategy:** If implementation fails tests, revert to previous working state before proceeding

#### RE-2.3: Implement multi-RRULE merge-sort

- **Status:** Completed
- **File:** `src/Recurrence/RecurrenceExpander.php` (MODIFY)
- **Depends on:** RE-2.2
- **Description:** Add support for multiple `RRULE` properties on a single component. RFC 5545 allows this (though rare in practice). When multiple RRULEs exist, the expander must call `RecurrenceGenerator::generate()` for each RRULE, then merge-sort the results chronologically and deduplicate.
- **Algorithm:**
  1. Parse all RRULEs into an array
  2. For each RRULE, call `RecurrenceGenerator::generate()` with that RRULE, DTSTART, rangeEnd, and empty exdates/rdates arrays
  3. Collect all generators from all RRULEs
  4. Merge-sort all occurrences from all generators chronologically
  5. Deduplicate: if two occurrences have the same start datetime, keep only one
  6. Apply EXDATEs and RDATEs as in RE-2.2
- **Implementation:** Create a private `mergeSortedGenerators(Generator[] $generators): Generator` method that uses an array-based priority queue:
  1. Read the first value from each generator into a buffer array
  2. Sort the buffer by timestamp
  3. Yield the smallest, refill from that generator
  4. Skip duplicates (same timestamp as previous yield)
  5. Continue until all generators are exhausted
- **Acceptance Criteria:**
  - [x] `mergeSortedGenerators()` accepts an array of `Generator` objects
  - [x] Output is sorted chronologically (ascending by timestamp)
  - [x] Duplicate dates (same timestamp from different RRULEs) appear only once
  - [x] The `expand()` method detects `count($rrules) > 1` and uses `mergeSortedGenerators()`
  - [x] For `count($rrules) === 1`, the single generator is used directly (no merge overhead)
  - [x] A shared EXDATE set is applied to the merged stream (not per-RRULE)
  - [x] RDATEs are merged into the combined stream after RRULE merge
- **Intermediate Validation:** Test multi-RRULE functionality after implementing merge-sort before adding EXDATE/RDATE logic
- **Validation Checkpoint:** Run unit tests for RE-2.3 to validate multi-RRULE functionality
- **Rollback Strategy:** If implementation fails tests, revert to previous working state before proceeding

#### RE-2.4: Unit tests for `RecurrenceExpander`

- **Status:** Completed
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
  - [x] All test cases listed above are implemented and pass
  - [x] Tests use programmatic component construction (not parsing raw iCalendar strings)
  - [x] Tests follow existing PHPUnit patterns in the project (method naming, assertions)
  - [x] `vendor/bin/phpunit tests/Recurrence/RecurrenceExpanderTest.php` passes with 0 failures, 0 errors
- **Intermediate Validation:** Run tests after implementing each major test category (RRULE, EXDATE, RDATE, multi-RRULE)
- **Validation Checkpoint:** Run full `composer test` after RE-2.4
- **Rollback Strategy:** If tests fail, revert to previous working state before proceeding

---

### Epic RE-3: Component Integration

> Add EXDATE/RDATE/RRULE methods to components and wire up the convenience `getOccurrences()` API via a shared trait.

#### RE-3.1: Add `setRrule` / `getRrule` to VTodo

- **Status:** Completed
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
  - [x] `VTodo::setRrule(string $rrule)` exists and returns `self` for method chaining
  - [x] `VTodo::getRrule(): ?string` exists
  - [x] Methods are identical to VEvent pattern
  - [x] `setRrule()` removes any existing RRULE before adding the new one (single-value semantics)
  - [x] `VTodo::getRrule()` returns `?string` — the raw RRULE string or `null`
  - [x] Round-trip works: `$todo->setRrule('FREQ=DAILY;COUNT=3')->getRrule()` returns `'FREQ=DAILY;COUNT=3'`
  - [x] Method bodies are identical to the VEvent pattern (use `GenericProperty::create()`)
- **Validation Checkpoint:** Run unit tests for RE-3.1 to validate VTodo RRULE methods
- **Rollback Strategy:** If implementation fails tests, revert to previous working state before proceeding
- **Parallel Task:** Can be done in parallel with RE-2.x tasks (no dependencies between RE-3.1 and RE-2.x)

#### RE-3.2: Create `RecurrenceTrait`

- **Status:** Completed
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
  - [x] Trait is in file `src/Component/Traits/RecurrenceTrait.php`
  - [x] Namespace is `Icalendar\Component\Traits`
  - [x] File has `declare(strict_types=1)`
  - [x] All 8 methods listed above are implemented
  - [x] `addExdate()` / `addRdate()` use `new GenericProperty('EXDATE', new TextValue($exdate), $params)` (or equivalent via `GenericProperty::create()` plus parameter setting)
  - [x] `addExdate()` does NOT call `removeProperty()` — it accumulates
  - [x] `setExdate()` DOES call `removeProperty('EXDATE')` before adding
  - [x] `getExdates()` returns `string[]` (raw iCalendar values, not parsed objects)
  - [x] `getOccurrences()` creates a fresh `RecurrenceExpander` each call (no caching)
  - [x] `getOccurrences()` uses `yield from` to delegate to the expander's generator
  - [x] No method name conflicts with existing component methods (`setRrule`/`getRrule` are NOT in the trait)
- **Validation Checkpoint:** Run unit tests for RE-3.2 to validate the RecurrenceTrait methods
- **Rollback Strategy:** If implementation fails tests, revert to previous working state before proceeding

#### RE-3.3: Apply `RecurrenceTrait` to VEvent, VTodo, VJournal

- **Status:** Completed
- **Files:** `src/Component/VEvent.php`, `src/Component/VTodo.php`, `src/Component/VJournal.php` (MODIFY)
- **Depends on:** RE-3.1, RE-3.2
- **Description:** Add `use RecurrenceTrait;` to each of the three component classes. Add the import statement `use Icalendar\Component\Traits\RecurrenceTrait;` at the top of each file.
- **Acceptance Criteria:**
  - [x] `VEvent` class has `use RecurrenceTrait;` inside the class body
  - [x] `VTodo` class has `use RecurrenceTrait;` inside the class body
  - [x] `VJournal` class has `use RecurrenceTrait;` inside the class body
  - [x] Each file has the `use Icalendar\Component\Traits\RecurrenceTrait;` import statement
  - [x] No method conflicts — existing `setRrule`/`getRrule` on each component are not duplicated by the trait
  - [x] `composer phpstan` passes at level 9 with no new errors
  - [x] All existing tests still pass (`composer test`)
- **Validation Checkpoint:** Run `composer test` after RE-3.3 to test component integration
- **Rollback Strategy:** If implementation fails tests, revert to previous working state before proceeding

#### RE-3.4: Unit tests for `RecurrenceTrait` on components

- **Status:** Completed
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
  - [x] All test cases listed above are implemented and pass
  - [x] Tests cover VEvent, VTodo, and VJournal
  - [x] Tests verify round-trip property storage (set then get)
  - [x] Tests verify `getOccurrences()` integration end-to-end
  - [x] `vendor/bin/phpunit tests/Component/RecurrenceTraitTest.php` passes with 0 failures, 0 errors
- **Validation Checkpoint:** Run `composer test` after RE-3.4
- **Rollback Strategy:** If tests fail, revert to previous working state before proceeding

---

### Epic RE-4: Verification & Quality

> Ensure all code passes static analysis and the full test suite.

#### RE-4.1: Full test suite and static analysis pass

- **Status:** Completed
- **Depends on:** RE-2.4, RE-3.4
- **Description:** Run the full test suite and static analysis to verify nothing is broken and all new code meets quality standards.
- **Acceptance Criteria:**
  - [x] `composer test` passes with 0 failures, 0 errors
  - [x] `composer phpstan` passes at level 9 with no new errors
  - [x] No new test warnings introduced (existing environment warnings are acceptable)
  - [x] All new files follow PSR-4 autoloading conventions
  - [x] All new files have `declare(strict_types=1)`

#### RE-4.2: Update Documentation

- **Status:** Completed
- **File:** `docs/USAGE.md` (or `README.md`)
- **Description:** Document the new Recurrence Expansion features (`getOccurrences`, `Occurrence` object) with code examples.
- **Acceptance Criteria:**
  - [x] `docs/USAGE.md` (or `README.md`) includes a section on "Recurrence Expansion"
  - [x] Code example shows how to call `getOccurrences()` on a `VEvent`
  - [x] Code example shows how to iterate over `Occurrence` objects and access start/end dates
  - [x] Documentation explains the optional `$rangeEnd` parameter and its requirement for unbounded rules
- **Validation Checkpoint:** Full system verification - this is the final checkpoint
- **Rollback Strategy:** If verification fails, revert to previous working state before proceeding

---

### Implementation Order

Tasks should be completed in this order (respecting dependencies):

**Phase 1: Foundation**
1. **RE-1.1** — Occurrence class (zero dependencies)
2. **RE-1.2** — Occurrence tests

**Phase 2: Core Service**  
3. **RE-2.1** — RecurrenceExpander with property extraction helpers
4. **RE-2.2** — RecurrenceExpander `expand()` method (single RRULE)
5. **RE-2.3** — Multi-RRULE merge-sort
6. **RE-2.4** — RecurrenceExpander tests

**Phase 3: Component Integration**
7. **RE-3.1** — VTodo `setRrule`/`getRrule` (🔄 **PARALLEL**: can be done in parallel with RE-2.x)
8. **RE-3.2** — RecurrenceTrait
9. **RE-3.3** — Apply trait to components
10. **RE-3.4** — RecurrenceTrait tests

**Phase 4: Final Verification & Documentation**
11. **RE-4.1** — Full verification
12. **RE-4.2** — Update Documentation

**Incremental Validation Checkpoints:**
- After RE-1.2: Verify Occurrence class works correctly
- After RE-2.2: Validate single RRULE expansion
- After RE-2.3: Confirm multi-RRULE functionality  
- After RE-3.3: Test component integration
- After RE-4.1: Full system verification

### Files Summary

| Action | File | Task | Lines of Code (est.) |
|--------|------|------|---------------------|
| CREATE | `src/Recurrence/Occurrence.php` | RE-1.1 | ~30 |
| CREATE | `src/Recurrence/RecurrenceExpander.php` | RE-2.1, RE-2.2, RE-2.3 | ~250 |
| CREATE | `src/Component/Traits/RecurrenceTrait.php` | RE-3.2 | ~80 |
| MODIFY | `src/Component/VTodo.php` | RE-3.1, RE-3.3 | ~20 |
| MODIFY | `src/Component/VEvent.php` | RE-3.3 | ~5 |
| MODIFY | `src/Component/VJournal.php` | RE-3.3 | ~5 |
| MODIFY | `docs/USAGE.md` | RE-4.2 | ~50 |
| CREATE | `tests/Recurrence/OccurrenceTest.php` | RE-1.2 | ~50 |
| CREATE | `tests/Recurrence/RecurrenceExpanderTest.php` | RE-2.4 | ~200 |
| CREATE | `tests/Component/RecurrenceTraitTest.php` | RE-3.4 | ~150 |

**Total Estimated New Code:** ~840 lines
**Total Files:** 10 (6 new, 4 modified)

---

## Development Guidelines

### For Coding Agents

1. **Always check existing patterns first** - Look at `src/Recurrence/RRule.php` for value object patterns, `src/Component/VEvent.php` for component method patterns, and `tests/Recurrence/RecurrenceGeneratorTest.php` for test patterns.

2. **Run tests incrementally** - Don't wait until the end. Use the validation checkpoints listed in each task to verify progress.

3. **Use rollback strategy** - If a task's tests fail and you can't quickly fix them, revert to the last known working state before proceeding.

4. **Parallel opportunities** - RE-3.1 (VTodo RRULE methods) can be implemented in parallel with RE-2.x tasks since it has no dependencies on them.

5. **Follow strict typing** - All new files must have `declare(strict_types=1)` and follow PSR-4 autoloading.

### Quality Gates

Before marking any task complete, ensure:
- [ ] All acceptance criteria checkboxes are satisfied
- [ ] `composer test` passes for the specific test file
- [ ] `composer phpstan` passes at level 9 with no new errors
- [ ] No test warnings introduced (existing environment warnings are acceptable)

---

## Future Considerations

-   Performance optimizations for extremely large iCalendar files.
-   Expanding lenient mode warning collection to other properties.
-   Potentially adding support for other iCalendar extensions if they become critical.
