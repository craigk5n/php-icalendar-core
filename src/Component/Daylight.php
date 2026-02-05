<?php

declare(strict_types=1);

namespace Icalendar\Component;

/**
 * DAYLIGHT observance component
 */
class Daylight extends AbstractComponent
{
    public function getName(): string
    {
        return 'DAYLIGHT';
    }
}
