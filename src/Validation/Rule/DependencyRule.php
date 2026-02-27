<?php

declare(strict_types=1);

namespace Icalendar\Validation\Rule;

use Icalendar\Component\ComponentInterface;
use Icalendar\Validation\ValidationResult;

final class DependencyRule extends AbstractRule
{
    private string $dependentProperty;
    private string $requiredProperty;
    private string $errorCode;
    /** @var string[] */
    private array $componentNames;

    /**
     * @param string[] $componentNames
     */
    public function __construct(
        string $dependentProperty,
        string $requiredProperty,
        string $errorCode,
        array $componentNames = []
    ) {
        $this->dependentProperty = $dependentProperty;
        $this->requiredProperty = $requiredProperty;
        $this->errorCode = $errorCode;
        $this->componentNames = $componentNames;
    }

    #[\Override]
    public function validate(ComponentInterface $component): ValidationResult
    {
        $hasDependent = $component->getProperty($this->dependentProperty) !== null;
        $hasRequired = $component->getProperty($this->requiredProperty) !== null;

        if ($hasDependent && !$hasRequired) {
            return $this->createError(
                $this->errorCode,
                "{$component->getName()} {$this->dependentProperty} requires {$this->requiredProperty} property",
                $component->getName(),
                $this->dependentProperty
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
