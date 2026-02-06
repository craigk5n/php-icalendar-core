<?php

declare(strict_types=1);

namespace Icalendar\Tests\Value;

use Icalendar\Value\ValueInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ValueInterfaceTest extends TestCase
{
    private ValueInterface|MockObject $value;

    protected function setUp(): void
    {
        $this->value = $this->createMock(ValueInterface::class);
    }

    public function testGetTypeReturnsString(): void
    {
        $this->value->expects($this->once())
            ->method('getType')
            ->willReturn('TEXT');

        $this->assertEquals('TEXT', $this->value->getType());
    }

    public function testGetRawValue(): void
    {
        $rawValue = 'Test value';

        $this->value->expects($this->once())
            ->method('getRawValue')
            ->willReturn($rawValue);

        $this->assertSame($rawValue, $this->value->getRawValue());
    }

    public function testSerialize(): void
    {
        $serialized = 'Test value';

        $this->value->expects($this->once())
            ->method('serialize')
            ->willReturn($serialized);

        $this->assertEquals($serialized, $this->value->serialize());
    }

    public function testIsDefault(): void
    {
        $this->value->expects($this->once())
            ->method('isDefault')
            ->willReturn(true);

        $this->assertTrue($this->value->isDefault());
    }

    public function testIsNotDefault(): void
    {
        $this->value->expects($this->once())
            ->method('isDefault')
            ->willReturn(false);

        $this->assertFalse($this->value->isDefault());
    }
}