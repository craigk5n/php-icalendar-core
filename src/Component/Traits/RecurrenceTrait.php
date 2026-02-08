<?php

declare(strict_types=1);

namespace Icalendar\Component\Traits;

use DateTimeInterface;
use Generator;
use Icalendar\Property\GenericProperty;
use Icalendar\Recurrence\Occurrence;
use Icalendar\Recurrence\RecurrenceExpander;

/**
 * Trait for components that support recurrence (VEVENT, VTODO, VJOURNAL).
 *
 * Provides property management for EXDATE and RDATE, and convenience
 * methods for recurrence expansion.
 *
 * @uses \Icalendar\Component\ComponentInterface
 */
trait RecurrenceTrait
{
    /**
     * Add an EXDATE (exception date) to the component.
     *
     * RFC 5545 allows multiple EXDATE properties.
     *
     * @param string $exdate The date/time string
     * @param array<string, string> $params Optional parameters (e.g., ['VALUE' => 'DATE', 'TZID' => '...'])
     * @return $this
     */
    public function addExdate(string $exdate, array $params = []): self
    {
        $property = GenericProperty::create('EXDATE', $exdate);
        foreach ($params as $name => $value) {
            $property->setParameter($name, $value);
        }
        $this->addProperty($property);
        return $this;
    }

    /**
     * Set a single EXDATE, removing all existing ones.
     *
     * @param string $exdate The date/time string
     * @param array<string, string> $params Optional parameters
     * @return $this
     */
    public function setExdate(string $exdate, array $params = []): self
    {
        $this->removeProperty('EXDATE');
        return $this->addExdate($exdate, $params);
    }

    /**
     * Get all EXDATE values from the component.
     *
     * @return array<string>
     */
    public function getExdates(): array
    {
        $values = [];
        /** @var \Icalendar\Property\PropertyInterface[] $props */
        $props = $this->getAllProperties('EXDATE');
        foreach ($props as $prop) {
            $values[] = $prop->getValue()->getRawValue();
        }
        return $values;
    }

    /**
     * Add an RDATE (recurrence date) to the component.
     *
     * @param string $rdate The date/time string
     * @param array<string, string> $params Optional parameters
     * @return $this
     */
    public function addRdate(string $rdate, array $params = []): self
    {
        $property = GenericProperty::create('RDATE', $rdate);
        foreach ($params as $name => $value) {
            $property->setParameter($name, $value);
        }
        $this->addProperty($property);
        return $this;
    }

    /**
     * Set a single RDATE, removing all existing ones.
     *
     * @param string $rdate The date/time string
     * @param array<string, string> $params Optional parameters
     * @return $this
     */
    public function setRdate(string $rdate, array $params = []): self
    {
        $this->removeProperty('RDATE');
        return $this->addRdate($rdate, $params);
    }

    /**
     * Get all RDATE values from the component.
     *
     * @return array<string>
     */
    public function getRdates(): array
    {
        $values = [];
        /** @var \Icalendar\Property\PropertyInterface[] $props */
        $props = $this->getAllProperties('RDATE');
        foreach ($props as $prop) {
            $values[] = $prop->getValue()->getRawValue();
        }
        return $values;
    }

    /**
     * Expand the recurrence rules into a generator of Occurrence objects.
     *
     * @param DateTimeInterface|null $rangeEnd Optional end date for expansion
     * @return Generator<int, Occurrence>
     * @yield Occurrence
     */
    public function getOccurrences(?DateTimeInterface $rangeEnd = null): Generator
    {
        $expander = new RecurrenceExpander();
        $i = 0;
        foreach ($expander->expand($this, $rangeEnd) as $occurrence) {
            yield $i++ => $occurrence;
        }
    }

    /**
     * Expand the recurrence rules into an array of Occurrence objects.
     *
     * @param DateTimeInterface|null $rangeEnd Optional end date for expansion
     * @return array<int, Occurrence>
     */
    public function getOccurrencesArray(?DateTimeInterface $rangeEnd = null): array
    {
        return iterator_to_array($this->getOccurrences($rangeEnd), false);
    }

    // Abstract methods from AbstractComponent/ComponentInterface that the trait needs
    abstract public function addProperty(\Icalendar\Property\PropertyInterface $property): void;
    abstract public function removeProperty(string $name): void;
    abstract public function getAllProperties(?string $name = null): array;
}
