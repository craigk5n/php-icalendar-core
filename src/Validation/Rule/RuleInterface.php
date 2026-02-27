<?php

declare(strict_types=1);

namespace Icalendar\Validation\Rule;

use Icalendar\Component\ComponentInterface;
use Icalendar\Validation\ValidationResult;

interface RuleInterface
{
    public function validate(ComponentInterface $component): ValidationResult;

    public function appliesTo(string $componentName): bool;
}
