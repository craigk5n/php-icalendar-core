<?php

declare(strict_types=1);

namespace Icalendar\Component;

/**
 * VTIMEZONE component
 */
class VTimezone extends AbstractComponent
{
    public function getName(): string
    {
        return 'VTIMEZONE';
    }
}
