<?php

declare(strict_types=1);

namespace Icalendar\Tests\Value;

use Icalendar\Value\AbstractValue;
use PHPUnit\Framework\TestCase;

class AbstractValueTest extends TestCase
{
    private TestValue $value;

    #[\Override]
    protected function setUp(): void
    {
        $this->value = new TestValue();
    }

    public function testIsDefaultReturnsFalse(): void
    {
        $this->assertFalse($this->value->isDefault());
    }

    public function testGetTypeReturnsString(): void
    {
        $this->assertEquals('TEST', $this->value->getType());
    }

    public function testGetRawValue(): void
    {
        $this->assertEquals('test-value', $this->value->getRawValue());
    }

    public function testSerialize(): void
    {
        $this->assertEquals('test-value', $this->value->serialize());
    }
}

/**
 * Concrete implementation for testing AbstractValue
 */
class TestValue extends AbstractValue
{
    #[\Override]
    public function getType(): string
    {
        return 'TEST';
    }

    #[\Override]
    public function getRawValue(): string
    {
        return 'test-value';
    }

    #[\Override]
    public function serialize(): string
    {
        return 'test-value';
    }
}