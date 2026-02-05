<?php

declare(strict_types=1);

namespace Icalendar\Component;

/**
 * VFREEBUSY component
 */
class VFreeBusy extends AbstractComponent
{
    public function getName(): string
    {
        return 'VFREEBUSY';
    }
}
