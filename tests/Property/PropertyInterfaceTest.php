<?php

declare(strict_types=1);

namespace Icalendar\Tests\Property;

use Icalendar\Property\PropertyInterface;
use Icalendar\Value\ValueInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PropertyInterfaceTest extends TestCase
{
    private PropertyInterface|MockObject $property;

    protected function setUp(): void
    {
        $this->property = $this->createMock(PropertyInterface::class);
    }

    public function testGetNameReturnsString(): void
    {
        $this->property->expects($this->once())
            ->method('getName')
            ->willReturn('SUMMARY');

        $this->assertEquals('SUMMARY', $this->property->getName());
    }

    public function testGetValue(): void
    {
        $value = $this->createMock(ValueInterface::class);

        $this->property->expects($this->once())
            ->method('getValue')
            ->willReturn($value);

        $this->assertSame($value, $this->property->getValue());
    }

    public function testSetValue(): void
    {
        $value = $this->createMock(ValueInterface::class);

        $this->property->expects($this->once())
            ->method('setValue')
            ->with($value);

        $this->property->setValue($value);
    }

    public function testGetParameter(): void
    {
        $this->property->expects($this->once())
            ->method('getParameter')
            ->with('LANGUAGE')
            ->willReturn('en-US');

        $this->assertEquals('en-US', $this->property->getParameter('LANGUAGE'));
    }

    public function testGetParameterReturnsNullForMissingParameter(): void
    {
        $this->property->expects($this->once())
            ->method('getParameter')
            ->with('NONEXISTENT')
            ->willReturn(null);

        $this->assertNull($this->property->getParameter('NONEXISTENT'));
    }

    public function testGetParameters(): void
    {
        $parameters = [
            'LANGUAGE' => 'en-US',
            'ENCODING' => 'QUOTED-PRINTABLE',
        ];

        $this->property->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->assertSame($parameters, $this->property->getParameters());
    }

    public function testSetParameter(): void
    {
        $this->property->expects($this->once())
            ->method('setParameter')
            ->with('LANGUAGE', 'en-US');

        $this->property->setParameter('LANGUAGE', 'en-US');
    }

    public function testRemoveParameter(): void
    {
        $this->property->expects($this->once())
            ->method('removeParameter')
            ->with('LANGUAGE');

        $this->property->removeParameter('LANGUAGE');
    }
}