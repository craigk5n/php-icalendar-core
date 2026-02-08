<?php

declare(strict_types=1);

namespace Icalendar\Component;

/**
 * Generic component for unknown component types
 */
class GenericComponent extends AbstractComponent
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = strtoupper($name);
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }
}
