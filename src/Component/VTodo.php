<?php

declare(strict_types=1);

namespace Icalendar\Component;

/**
 * VTODO component
 */
class VTodo extends AbstractComponent
{
    public function getName(): string
    {
        return 'VTODO';
    }
}
