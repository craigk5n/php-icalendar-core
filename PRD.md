# Product Requirements Document — PHP iCalendar Core

## 1. Introduction

### 1.1 Purpose of this document

This document states **what** PHP iCalendar Core must do and **which decisions the library has made** where the underlying specifications leave a choice.

It is written to be sufficient, alongside the referenced RFCs, to rebuild the library. It deliberately does **not** restate the RFCs: RFC 5545 alone runs to some 200 pages, and duplicating it here would guarantee the copy drifts from the original. Where behaviour is fully determined by an RFC, this document cites the section and moves on. Where the RFC permits latitude — error handling, API shape, resource limits — this document specifies the choice and the reasoning.

It also does not record implementation status, test counts, or coverage figures. Those belong in `STATUS.md` and the issue tracker, and putting them here is what caused earlier revisions of this file to go stale.

### 1.2 Product goal

A dependency-free PHP library for reading and writing iCalendar data that is correct by default, honest about malformed input, and usable on files larger than memory.

### 1.3 Scope

**In scope:** parsing, validation and serialisation of iCalendar streams; the component and property model; recurrence expansion; timezone resolution; the extensions listed in §5.

**Out of scope:** calendar storage or synchronisation (CalDAV, RFC 4791); network retrieval of remote resources; interpretation of rich-text payloads beyond storing and re-emitting them; timezone *data* — the library resolves against observances present in the input or against PHP's database, and does not ship its own.

## 2. Normative references

| Specification | Role |
|---|---|
| RFC 5545 | Core iCalendar format. The primary requirements source. |
| RFC 6868 | Parameter value encoding (`^n`, `^^`, `^'`). |
| RFC 7986 | Additional properties: `IMAGE`, `COLOR`, `CONFERENCE`, `REFRESH-INTERVAL`, `NAME`, `SOURCE`. |
| RFC 7953 | `VAVAILABILITY` / `AVAILABLE`. |
| RFC 9073 | `STYLED-DESCRIPTION`, `PARTICIPANT`. |
| RFC 7265 | jCal JSON representation. |

"MUST", "SHOULD" and "MAY" are used per RFC 2119.

## 3. Users and use cases

- **Importing third-party calendars.** Feeds from real-world producers are frequently non-conformant. The importer needs maximum data recovery *plus* a record of what was wrong — never a silently invented value.
- **Generating calendars.** Output must be accepted by strict consumers, so serialisation correctness matters more than leniency.
- **Validating calendars.** The library must be usable as a conformance checker, reporting violations with enough detail to locate them.
- **Processing large files.** Files may exceed available memory.

## 4. Functional requirements

### 4.1 Parsing modes

The parser MUST offer two modes, selected at construction, defaulting to strict.

- **Strict** — any violation of RFC 5545 syntax or of a value's data type MUST raise an exception identifying the offending content line.
- **Lenient** — parsing MUST continue, collecting a diagnostic per violation, retrievable after the parse.

**The lenient contract (the library's own decision, not the RFC's):**

> A value that cannot be parsed MUST be reported and MUST NOT be replaced by a substitute.

An unparseable property is dropped and recorded; it is never coerced into a default, a current timestamp, or an empty value. This exists because the opposite behaviour is silent data corruption: a `DTSTART` that cannot be understood becoming "now" produces a calendar that looks valid and is wrong. Both modes must agree on *what* is invalid; they differ only in whether the parse continues.

Mode MUST be a property of the instance and apply consistently to every subsystem it delegates to.

### 4.2 Lexing

- Line endings (CRLF, LF, CR) MUST be accepted and normalised.
- Folded lines MUST be unfolded per RFC 5545 §3.1: remove the CRLF and the single whitespace character following it. That whitespace is the fold marker and is not content.
- A content line without a `:` separator is malformed and MUST be handled per the active mode.
- File input MUST be read incrementally rather than loaded whole, and unfolding MUST work across read-buffer boundaries.

### 4.3 Value types

The parser MUST implement the RFC 5545 §3.3 value types: `BINARY`, `BOOLEAN`, `CAL-ADDRESS`, `DATE`, `DATE-TIME`, `DURATION`, `FLOAT`, `INTEGER`, `PERIOD`, `RECUR`, `TEXT`, `TIME`, `URI`, `UTC-OFFSET`.

Structured values whose grammar is not a single scalar — `GEO` (`latitude ";" longitude`) — MUST have their own parser and writer rather than being routed through `TEXT`. **Any property whose value contains structural delimiters MUST NOT be serialised as `TEXT`**, because TEXT escaping would escape those delimiters and destroy the structure.

**Value type resolution:**

1. A `VALUE` parameter, if present, selects the type — but only from the set that property permits. A property MUST NOT be re-typed to something it does not allow; in particular `VALUE=TEXT` MUST NOT be usable to bypass validation.
2. Otherwise the property's default type applies.
3. A property unknown to the library falls back to `TEXT`, and its `VALUE` parameter is not policed — an extension's type is not ours to constrain.

Every property with a defined type SHOULD appear in the property/type map. A property absent from it silently inherits `TEXT`, which cannot fail, so absence means no validation.

**DATE-TIME forms** (§3.3.5) MUST be distinguished and preserved: floating (no suffix), UTC (`Z` suffix), and zoned (`TZID` parameter). UTC-ness MUST be recorded from the source, never inferred from the host timezone — a floating value carries no zone, and inferring one silently promotes it.

**TEXT** escaping MUST cover backslash, semicolon, comma and newline (§3.3.11), and MUST exclude the CONTROL set (`%x00-08`, `%x0A-1F`, `%x7F`).

Multi-valued TEXT properties (`CATEGORIES`, `RESOURCES`) are a comma-separated list. Each item MUST be escaped individually and joined with **literal** separators, so a comma inside a value stays distinguishable from a separator between values.

### 4.4 Components

The library MUST model: `VCALENDAR`, `VEVENT`, `VTODO`, `VJOURNAL`, `VFREEBUSY`, `VTIMEZONE` (with `STANDARD` / `DAYLIGHT`), `VALARM`, `VAVAILABILITY` / `AVAILABLE`, `PARTICIPANT`.

- Components MUST nest arbitrarily and expose their properties and children.
- An unrecognised component MUST NOT abort a lenient parse; it is retained generically with its properties reachable. Registering custom component *classes* is not currently supported — a deliberate limitation, and any change to it is a scope decision.
- Nesting depth MUST be bounded (§7.3).

### 4.5 Validation

Validation MUST be available independently of parsing, and MUST report every violation found rather than stopping at the first. Errors from one component MUST NOT displace those already found in another.

Required checks: mandatory properties per component; mutually exclusive properties (e.g. `DTEND` with `DURATION`); value ranges; `TZID` references resolving to a `VTIMEZONE` present in the calendar; recurrence rule well-formedness; and **single-occurrence properties** — those RFC 5545 marks as occurring at most once MUST be reported when repeated.

Cardinality is per component and MUST NOT be generalised across them: `DESCRIPTION` is single-occurrence on `VEVENT` and `VTODO` but MAY repeat on `VJOURNAL` (§3.6.3).

The validator MUST offer the same strict/lenient distinction as the parser, selecting the severity of violations that still leave usable data.

### 4.6 Recurrence

The library MUST expand `RRULE`, `RDATE` and `EXDATE` into concrete occurrences per RFC 5545 §3.8.5, exposing both a lazy generator and an array convenience form.

Requirements the RFC's algorithm implies, stated because they are easy to get wrong:

- `EXDATE` applies **after** each `RRULE`'s `COUNT` limit. A `COUNT=5` rule with one excluded date yields four occurrences, not five.
- Multiple `RRULE`s on one component MUST be unioned, ordered and deduplicated.
- An `EXDATE` with `VALUE=DATE` MUST exclude every occurrence on that date; with `VALUE=DATE-TIME`, only the exact instant.
- With no `RRULE`, the set is `{DTSTART} ∪ RDATEs \ EXDATEs`.
- Occurrence end times derive from `DTEND`, else `DURATION`, else `DUE` (`VTODO`), else are undefined.
- `UNTIL` MUST share its `DTSTART`'s value type (§3.3.10). Comparing a floating start against a UTC `UNTIL` is host-dependent and MUST NOT be relied upon.
- An unbounded rule (no `COUNT`, no `UNTIL`) MUST require an explicit range bound rather than expanding indefinitely.

Expansion MUST NOT hold the whole set in memory when the generator form is used.

### 4.7 Timezones

`VTIMEZONE` observances define **recurring** onsets. Building a transition table MUST expand each observance's `RRULE`, not only its `DTSTART` — otherwise offsets are wrong for every date outside the first period.

- `DTSTART` is itself an onset (§3.6.5) and MUST be included even when it does not match its own rule pattern.
- Before the first transition, the offset is the earliest observance's `TZOFFSETFROM`. It MUST NOT default to UTC.
- Because observances are unbounded, expansion is bounded by a horizon, and a query beyond it MUST extend the table rather than return a wrong answer.

### 4.8 Writing

Output MUST be RFC 5545 conformant:

- Lines folded at 75 octets, splitting on octet — not character — boundaries, so multi-byte sequences are never divided.
- CRLF terminators throughout, including the final line.
- Parameter values encoded per RFC 6868, and quoted when containing `:`, `;` or `,`.
- Structural delimiters preserved literally (§4.3).

A parse → write → parse round trip MUST preserve semantics. Writing SHOULD be available in a form that validates first.

### 4.9 Extensions

- **RFC 7986** — `IMAGE` (`VALUE=URI` or `BINARY`), `COLOR` (TEXT), `CONFERENCE` (URI), `REFRESH-INTERVAL` (DURATION), `NAME`, `SOURCE`.
- **RFC 7953** — `VAVAILABILITY` / `AVAILABLE`, including recurrence within `AVAILABLE`.
- **RFC 9073** — `PARTICIPANT`; and `STYLED-DESCRIPTION`, whose payload is stored and re-emitted verbatim without interpretation. When `STYLED-DESCRIPTION` is present, a plain `DESCRIPTION` without `DERIVED=TRUE` MUST be omitted on both parse and write; with `DERIVED=TRUE` it is preserved.
- **RFC 7265** — export to jCal.
- **De facto** — `X-WR-CALNAME`, `X-WR-TIMEZONE`, `X-APPLE-STRUCTURED-LOCATION`.

## 5. Error reporting

### 5.1 Code scheme

Every diagnostic carries a stable machine-readable code:

```
ICAL-<CATEGORY>-<NNN>
```

`<CATEGORY>` identifies the subsystem or component; `<NNN>` is a zero-padded sequence within it. Codes are **append-only**: once published, a code's meaning MUST NOT be repurposed, because callers branch on them. Retire a code rather than redefine it.

Current categories: `PARSE`, `TYPE`, `COMP`, `SEC`, `IO`, `RRULE`, `TZ`, `VAL`, `WRITE`, and per-component families (`VEVENT`, `VTODO`, `VJOURNAL`, `VFB`, `ALARM`, `TZ-OBS`, `PART`, `AVAIL`, `VAVAIL`).

The authoritative list of codes lives with the exceptions that raise them — `ParseException`, `ValidationException`, `InvalidDataException` — and is **not duplicated here**, so the two cannot disagree. Adding a code means adding a constant there and using the next free number in its category.

### 5.2 Diagnostic content

A diagnostic MUST carry code, message, and — where known — the component, property, offending content line and line number. Lenient-mode diagnostics MUST distinguish severity, since not every violation is equally serious.

## 6. Non-functional requirements

### 6.1 Platform

PHP 8.1 or later. **No runtime dependencies** — this is a deliberate constraint: the library is intended to be safe to add to any project, so anything needed at runtime must be in PHP's standard library. The declared minimum MUST be exercised in CI, or it is only an assertion.

### 6.2 Memory

Parsing from a file MUST NOT require memory proportional to file size for the *tokenisation* stage. The resulting object graph is necessarily proportional to content; the input stream is not, and MUST NOT be.

### 6.3 Correctness under differing hosts

Behaviour MUST NOT depend on the host's configured timezone. Since UTC is the default on most servers and CI runners, a UTC-only test environment cannot detect such coupling, so conformance MUST be exercised under at least one non-UTC zone.

## 7. Security requirements

Input is untrusted. A calendar file is frequently fetched from a third party.

### 7.1 Resource exhaustion

Nesting depth MUST be bounded (default 100) and `data:` URI payloads limited (default 1 MB). Both MUST be configurable.

### 7.2 External entities

`<!ENTITY>` and `<!DOCTYPE>` markers MUST be rejected. Detection MUST NOT be defeated by the marker spanning a read-buffer boundary.

### 7.3 Server-side request forgery

URI schemes MUST be restricted to an allowlist (default `http`, `https`, `mailto`, `tel`, `urn`, `data`), and hosts resolving to private or loopback ranges MUST be rejected. Both MUST be configurable, since a legitimate deployment may need a private host.

### 7.4 Output sanitisation

Control characters MUST NOT reach output (§4.3). File paths accepted for reading MUST NOT carry a URI scheme.

## 8. Extensibility

Custom **value types** MUST be registrable on the parser and writer factories, keyed by type name. Custom **components** are not currently supported (§4.4).

## 9. Open questions

- Whether lenient mode should be configurable per-violation rather than global.
- Whether custom component registration should be supported, and what would own the mapping.
- Whether jCal import (not just export) is in scope.
