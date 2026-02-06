<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\ComponentInterface;
use Icalendar\Property\PropertyInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ComponentInterfaceTest extends TestCase
{
    private ComponentInterface|MockObject $component;

    protected function setUp(): void
    {
        $this->component = $this->createMock(ComponentInterface::class);
    }

    public function testGetNameReturnsString(): void
    {
        $this->component->expects($this->once())
            ->method('getName')
            ->willReturn('VEVENT');

        $this->assertEquals('VEVENT', $this->component->getName());
    }

    public function testAddProperty(): void
    {
        $property = $this->createMock(PropertyInterface::class);

        $this->component->expects($this->once())
            ->method('addProperty')
            ->with($property);

        $this->component->addProperty($property);
    }

    public function testGetProperty(): void
    {
        $property = $this->createMock(PropertyInterface::class);

        $this->component->expects($this->once())
            ->method('getProperty')
            ->with('SUMMARY')
            ->willReturn($property);

        $this->assertSame($property, $this->component->getProperty('SUMMARY'));
    }

    public function testGetPropertyReturnsNullForMissingProperty(): void
    {
        $this->component->expects($this->once())
            ->method('getProperty')
            ->with('NONEXISTENT')
            ->willReturn(null);

        $this->assertNull($this->component->getProperty('NONEXISTENT'));
    }

    public function testGetProperties(): void
    {
        $properties = [
            $this->createMock(PropertyInterface::class),
            $this->createMock(PropertyInterface::class),
        ];

        $this->component->expects($this->once())
            ->method('getProperties')
            ->willReturn($properties);

        $this->assertSame($properties, $this->component->getProperties());
    }

    public function testRemoveProperty(): void
    {
        $this->component->expects($this->once())
            ->method('removeProperty')
            ->with('SUMMARY');

        $this->component->removeProperty('SUMMARY');
    }

    public function testAddComponent(): void
    {
        $subComponent = $this->createMock(ComponentInterface::class);

        $this->component->expects($this->once())
            ->method('addComponent')
            ->with($subComponent);

        $this->component->addComponent($subComponent);
    }

    public function testGetComponentsWithoutType(): void
    {
        $components = [
            $this->createMock(ComponentInterface::class),
            $this->createMock(ComponentInterface::class),
        ];

        $this->component->expects($this->once())
            ->method('getComponents')
            ->with(null)
            ->willReturn($components);

        $this->assertSame($components, $this->component->getComponents());
    }

    public function testGetComponentsWithType(): void
    {
        $components = [
            $this->createMock(ComponentInterface::class),
        ];

        $this->component->expects($this->once())
            ->method('getComponents')
            ->with('VALARM')
            ->willReturn($components);

        $this->assertSame($components, $this->component->getComponents('VALARM'));
    }

    public function testRemoveComponent(): void
    {
        $subComponent = $this->createMock(ComponentInterface::class);

        $this->component->expects($this->once())
            ->method('removeComponent')
            ->with($subComponent);

        $this->component->removeComponent($subComponent);
    }

    public function testGetParent(): void
    {
        $parent = $this->createMock(ComponentInterface::class);

        $this->component->expects($this->once())
            ->method('getParent')
            ->willReturn($parent);

        $this->assertSame($parent, $this->component->getParent());
    }

    public function testGetParentReturnsNull(): void
    {
        $this->component->expects($this->once())
            ->method('getParent')
            ->willReturn(null);

        $this->assertNull($this->component->getParent());
    }

    public function testSetParent(): void
    {
        $parent = $this->createMock(ComponentInterface::class);

        $this->component->expects($this->once())
            ->method('setParent')
            ->with($parent);

        $this->component->setParent($parent);
    }

    public function testSetParentToNull(): void
    {
        $this->component->expects($this->once())
            ->method('setParent')
            ->with(null);

        $this->component->setParent(null);
    }
}