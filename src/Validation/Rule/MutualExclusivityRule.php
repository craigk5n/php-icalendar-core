<?php

declare(strict_types=1);

namespace Icalendar\Validation\Rule;

use Icalendar\Component\ComponentInterface;
use Icalendar\Validation\ValidationResult;

final class MutualExclusivityRule extends AbstractRule
{
    private string $property1;
    private string $property2;
    private string $errorCode;
    /** @var string[] */
    private array $componentNames;

    /** @param string[] $componentNames */
    public function __construct(string $property1, string $property2, string $errorCode, array $componentNames = [])
    {
        $this->property1 = $property1;
        $this->property2 = $property2;
        $this->errorCode = $errorCode;
        $this->componentNames = $componentNames;
    }

    #[\Override]
    public function validate(ComponentInterface $component): ValidationResult
    {
        $has1 = $component->getProperty($this->property1) !== null;
        $has2 = $component->getProperty($this->property2) !== null;

        if ($has1 && $has2) {
            return $this->createError(
                $this->errorCode,
                "{$component->getName()} cannot have both {$this->property1} and {$this->property2} properties",
                $component->getName(),
                $this->property1
            );
        }

        return ValidationResult::empty();
    }

    #[\Override]
    public function appliesTo(string $componentName): bool
    {
        if (empty($this->componentNames)) {
            return true;
        }
        return in_array($componentName, $this->componentNames, true);
    }
}
