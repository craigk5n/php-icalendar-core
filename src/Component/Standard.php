<?php

declare(strict_types=1);

namespace Icalendar\Component;

/**
 * STANDARD observance component
 */
class Standard extends AbstractComponent
{
    public function getName(): string
    {
        return 'STANDARD';
    }
}
