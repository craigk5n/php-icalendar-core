<?php

declare(strict_types=1);

namespace Icalendar\Component;

/**
 * VJOURNAL component
 */
class VJournal extends AbstractComponent
{
    public function getName(): string
    {
        return 'VJOURNAL';
    }
}
