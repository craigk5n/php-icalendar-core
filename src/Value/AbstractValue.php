<?php

declare(strict_types=1);

namespace Icalendar\Value;

/**
 * Abstract base class for all value types
 */
abstract class AbstractValue implements ValueInterface
{
    public function isDefault(): bool
    {
        return false;
    }
}
