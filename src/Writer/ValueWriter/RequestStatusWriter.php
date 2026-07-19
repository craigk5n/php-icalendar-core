<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for REQUEST-STATUS values (RFC 5545 §3.8.8.3).
 *
 * `statcode ";" statdesc [";" extdata]`, where the semicolons are *structural*.
 * Routing the value through the TEXT writer — the old behaviour, since the
 * property was unmapped — escaped them and produced `REQUEST-STATUS:2.0\;Success`,
 * which a conformant reader cannot split back into a code and a description.
 *
 * The value arriving here is already the canonical wire form, with any
 * semicolon that belongs to the description already escaped by whoever built
 * it, so it is emitted verbatim.
 */
final class RequestStatusWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                'RequestStatusWriter expects string, got ' . gettype($value)
            );
        }

        return trim($value);
    }

    #[\Override]
    public function getType(): string
    {
        return 'REQUEST-STATUS';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_string($value);
    }
}
