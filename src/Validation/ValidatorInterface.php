<?php

declare(strict_types=1);

namespace Icalendar\Validation;

use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VCalendar;
use Icalendar\Property\PropertyInterface;

interface ValidatorInterface
{
    public function validate(VCalendar $calendar): ValidationResult;

    public function validateSingleComponent(ComponentInterface $component): ValidationResult;

    public function validateProperty(PropertyInterface $property): ValidationResult;

    public function isValid(VCalendar $calendar): bool;

    /** @return array{WARNING: int, ERROR: int, FATAL: int} */
    public function getErrorCounts(VCalendar $calendar): array;
}
