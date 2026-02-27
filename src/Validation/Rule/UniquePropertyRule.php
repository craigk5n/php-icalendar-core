<?php

declare(strict_types=1);

namespace Icalendar\Validation\Rule;

use Icalendar\Component\ComponentInterface;
use Icalendar\Validation\ValidationResult;

final class UniquePropertyRule extends AbstractRule
{
    private string $propertyName;
    private string $ruleErrorCode;
    /** @var string[] */
    private array $componentNames;

    /** @param string[] $componentNames */
    public function __construct(string $propertyName, string $errorCode, array $componentNames = [])
    {
        $this->propertyName = $propertyName;
        $this->ruleErrorCode = $errorCode;
        $this->componentNames = $componentNames;
    }

    #[\Override]
    public function validate(ComponentInterface $component): ValidationResult
    {
        $properties = $component->getAllProperties($this->propertyName);
        if (count($properties) > 1) {
            return $this->createError(
                $this->ruleErrorCode,
                "{$component->getName()} MUST NOT have more than one {$this->propertyName} property",
                $component->getName(),
                $this->propertyName
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
