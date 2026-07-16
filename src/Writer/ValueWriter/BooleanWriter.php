<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for BOOLEAN values
 */
class BooleanWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        // The write path is stringly typed: ValueInterface::getRawValue() returns
        // string, so PropertyWriter can only ever hand us the parser's canonical
        // serialisation. Accept exactly that -- as IntegerWriter accepts its own.
        // Only 'TRUE'/'FALSE' verbatim: PHP's coercions ('1', 'yes', 'true') stay
        // rejected, which is what the strict-bool guard is for.
        if ($value === 'TRUE' || $value === 'FALSE') {
            return $value;
        }

        throw new \InvalidArgumentException('BooleanWriter expects bool, got ' . gettype($value));
    }

    #[\Override]
    public function getType(): string
    {
        return 'BOOLEAN';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_bool($value) || $value === 'TRUE' || $value === 'FALSE';
    }
}