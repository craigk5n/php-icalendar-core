# Changelog

All notable changes to this project are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-07-19

A correctness release. Most entries below are cases where the library accepted,
produced or reported something that a conformant reader would disagree with —
in several cases silently.

### Removed

- **`Icalendar\Validation\Rule\*`** — `RuleInterface`, `AbstractRule`,
  `UniquePropertyRule`, `RequiredPropertyRule`, `DependencyRule`,
  `MutualExclusivityRule`. A rule framework that no code path reached: no
  callers, no tests. `Validator` implements its checks directly.
- **`Icalendar\Timezone\*`** — `TimezoneResolver` and `TimezoneDatabase`, both
  empty classes with a `TODO` and no methods. Timezone resolution lives in
  `VTimezone`.

Both namespaces shipped in 1.1.5 but were inert, so nothing that used them
could have worked. Removing them changed no test, coverage or analysis result.

### Fixed

**Parsing no longer invents values.** In lenient mode a value that cannot be
parsed is now reported and dropped rather than replaced by a substitute.
Previously `DTSTART:` (empty), `DTSTART:now` and `DTSTART:tomorrow` were
accepted and resolved to the current time, producing a calendar that looked
valid and was wrong. Both modes now agree on what is invalid; they differ only
in whether parsing continues.

**Structural delimiters are no longer escaped away.** Three properties whose
grammar uses delimiters were being serialised as TEXT, which escaped those
delimiters and destroyed the structure:

| Property | Was | Now |
|---|---|---|
| `CATEGORIES` (multi-value) | `git\,release` — one category | `git,release` — two |
| `GEO` | `37.386013\;-122.082932` | `37.386013;-122.082932` |
| `REQUEST-STATUS` | `2.0\;Success` | `2.0;Success` |

A comma or semicolon *inside* a value is still escaped, so the two cases stay
distinguishable through a full round trip.

**Timezone offsets outside the first year.** `VTimezone` built one transition
per observance from its `DTSTART` and ignored `RRULE` entirely, so any date
past the last literal transition inherited that offset — summer 2028 resolving
to EST. Observance rules are now expanded. Also fixed while there: transition
lookup was broken for *every parsed* `VTIMEZONE` regardless of recurrence, as
parsed values are keyed differently from constructed ones and never matched;
and the offset before the first transition now comes from the earliest
observance's `TZOFFSETFROM` instead of defaulting to UTC.

**Validation errors were being discarded.** Recursion through the public
component entry point reset the error buffer, so each component validated wiped
everything found before it. Two invalid `VEVENT`s reported two errors instead of
four, and — because `VCALENDAR`'s own checks run first — `isValid()` returned
`true` for a calendar missing both `PRODID` and `VERSION` whenever any component
was present.

**Other fixes**

- `RRULE` validation now runs in both modes; invalid `BYDAY` parts are reported
  rather than skipped.
- Floating `DATE-TIME` values are no longer promoted to UTC. UTC-ness is taken
  from the source rather than inferred from the host clock, which had made
  behaviour depend on `date.timezone`.
- DEL (`0x7F`) is escaped in TEXT values, completing the RFC 5545 §3.3.11
  CONTROL set.
- `BOOLEAN` values serialise as `TRUE`/`FALSE`, and the boolean and float
  writers accept what the parsers produce, so parse → write round trips.
- Output ends with a trailing CRLF.
- `Writer::writeToFile()` no longer emits a raw PHP warning alongside the
  exception it already threw, and reports *why* the write failed.

### Added

- **`Validator::STRICT` / `Validator::LENIENT`** — selects whether violations
  that still leave usable data are reported as ERROR or WARNING. Defaults to
  strict, so existing callers are unaffected.
- **Single-occurrence property validation** (`ICAL-COMP-006`) — properties
  RFC 5545 permits at most once are reported when repeated. Cardinality is per
  component: `DESCRIPTION` may repeat on `VJOURNAL` but not on `VEVENT`.
- **`GEO` and `REQUEST-STATUS` validation** — structured parsers and writers
  (`ICAL-TYPE-016`, `ICAL-TYPE-017`). `GEO` checks two floats with latitude in
  [-90, 90] and longitude in [-180, 180]; `REQUEST-STATUS` checks a
  dot-separated numeric status code with a description.
- **`Writer::writeValidated()`** — validates before serialising.
- **`addRequestStatus()`** on `VEvent`, `VTodo`, `VJournal`, `VFreeBusy`.
- **URL accessors** on `VJournal` and `VFreeBusy`, via a shared trait.
- **`TZURL` and `SOURCE` mapped to URI** — a malformed value is now reportable
  in strict mode. While these properties were unmapped they inherited the TEXT
  default, which cannot fail, so no mode could reject one.
- **Date setters accept `DateTimeInterface` and parameters** —
  `setDtStart($date, ['TZID' => 'America/New_York'])`. Strings still work.
- **`VALUE` parameter allowlist** — a property may only be re-typed to a type it
  permits, so `VALUE=TEXT` can no longer be used to bypass validation.
- **Component validation recurses** into sub-components.

### Changed

- **`parseFile()` streams.** It now drives the chunked lexer instead of reading
  the file whole. Peak memory parsing a 2.9 MB calendar fell from 68.9 MB to
  34.0 MB. The XXE scan reads in chunks with an overlap, so a marker spanning a
  boundary is still caught.
- **`CATEGORIES` values are `TextListValue`.** `getValue()` on a `CATEGORIES`
  property returns `TextListValue` rather than `TextValue`; `GEO` and
  `REQUEST-STATUS` values carry their own types. All implement
  `ValueInterface`, so `getRawValue()` is unaffected.
- **`setCategories('')` clears the list** rather than storing an empty category.
- Shared behaviour moved into traits (`CategoriesTrait`, `UrlTrait`,
  `RequestStatusTrait`) rather than being copied per component.

### Upgrading

Most consumers need no changes. Watch for these:

1. **Malformed input now surfaces.** Calendars that previously parsed "clean"
   may now produce warnings, and `isValid()` may return `false` where it
   returned `true`. In every such case the older answer was wrong.
2. **If you referenced `Icalendar\Validation\Rule\*` or `Icalendar\Timezone\*`**,
   those namespaces are gone. Neither had a working implementation.
3. **If you type-check the object from `getValue()`** on `CATEGORIES`, `GEO` or
   `REQUEST-STATUS`, it is no longer `TextValue`. `getRawValue()` behaves as
   before.
4. **Output changes for the properties above**, because the previous output was
   not conformant.

[1.2.0]: https://github.com/craigk5n/php-icalendar-core/compare/v1.1.5...v1.2.0
