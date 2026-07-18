<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for GEO values (RFC 5545 §3.8.1.6).
 *
 * GEO is `latitude ";" longitude` where the semicolon is *structural*. Routing
 * GEO through the TEXT writer -- the old behaviour, since setGeo() stored a TEXT
 * value -- escaped that semicolon to `\;` and produced `GEO:37.386013\;...`,
 * which a conformant reader can no longer split. The value reaching us is the
 * already-canonical `lat;lon` string (from setGeo() or the validated GeoParser),
 * so it is emitted verbatim.
 */
final class GeoWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('GeoWriter expects string, got ' . gettype($value));
        }

        return trim($value);
    }

    #[\Override]
    public function getType(): string
    {
        return 'GEO';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_string($value);
    }
}
