<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\AbstractComponent;
use Icalendar\Component\ComponentInterface;
use Icalendar\Property\PropertyInterface;
use Icalendar\Value\ValueInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AbstractComponentTest extends TestCase
{
    private TestComponent $component;

    #[\Override]
    protected function setUp(): void
    {
        $this->component = new TestComponent();
    }

    public function testGetNameReturnsComponentName(): void
    {
        $this->assertEquals('TEST', $this->component->getName());
    }

    public function testAddProperty(): void
    {
        $property = $this->createMock(PropertyInterface::class);

        $this->component->addProperty($property);

        $properties = $this->component->getProperties();
        $this->assertCount(1, $properties);
        $this->assertSame($property, $properties[0]);
    }

    public function testGetPropertyReturnsMostRecentlyAdded(): void
    {
        $property1 = $this->createMockProperty('SUMMARY', 'First summary');
        $property2 = $this->createMockProperty('SUMMARY', 'Second summary');

        $this->component->addProperty($property1);
        $this->component->addProperty($property2);

        $result = $this->component->getProperty('SUMMARY');
        $this->assertSame($property2, $result);
    }

    public function testGetAllProperties(): void
    {
        $prop1 = $this->createMockProperty('ATTENDEE', 'user1@example.com');
        $prop2 = $this->createMockProperty('SUMMARY', 'Meeting');
        $prop3 = $this->createMockProperty('ATTENDEE', 'user2@example.com');

        $this->component->addProperty($prop1);
        $this->component->addProperty($prop2);
        $this->component->addProperty($prop3);

        // Test with name
        $attendees = $this->component->getAllProperties('ATTENDEE');
        $this->assertCount(2, $attendees);
        $this->assertSame($prop1, $attendees[0]);
        $this->assertSame($prop3, $attendees[1]);

        // Test without name
        $all = $this->component->getAllProperties();
        $this->assertCount(3, $all);
    }
    
    #[\Override]
    public function tearDown(): void
    {
        // Reset component state between tests
        unset($this->component);
    }
    
    public function testRemoveComponent(): void
    {
        $subComponent = $this->createMock(ComponentInterface::class);
        
        // Track calls to setParent
        $setParentCalls = [];
        $subComponent->method('setParent')
            ->willReturnCallback(function($parent) use (&$setParentCalls) {
                $setParentCalls[] = $parent;
            });
        
        $this->component->addComponent($subComponent);
        $this->component->removeComponent($subComponent);
        $components = $this->component->getComponents();
        $this->assertEmpty($components);
        
        // Verify setParent was called twice: once with component, once with null
        $this->assertCount(2, $setParentCalls);
        $this->assertSame($this->component, $setParentCalls[0]);
        $this->assertNull($setParentCalls[1]);
    }

    public function testRemoveComponentDoesNothingForNonExistentComponent(): void
    {
        $subComponent1 = $this->createMock(ComponentInterface::class);
        $subComponent2 = $this->createMock(ComponentInterface::class);

        $this->component->addComponent($subComponent1);
        $this->component->removeComponent($subComponent2);

        $components = $this->component->getComponents();
        $this->assertCount(1, $components);
        $this->assertSame($subComponent1, $components[0]);
    }

    public function testGetParentInitiallyNull(): void
    {
        $this->assertNull($this->component->getParent());
    }

    public function testSetParent(): void
    {
        $parent = $this->createMock(ComponentInterface::class);

        $this->component->setParent($parent);

        $this->assertSame($parent, $this->component->getParent());
    }

    public function testSetParentToNull(): void
    {
        $this->component->setParent(null);

        $this->assertNull($this->component->getParent());
    }

    private function createMockProperty(string $name, string $_value): PropertyInterface|MockObject
    {
        $property = $this->createMock(PropertyInterface::class);
        $property->method('getName')->willReturn($name);
        $property->method('getValue')->willReturn($this->createMock(ValueInterface::class));
        return $property;
    }
}

/**
 * Concrete implementation for testing AbstractComponent
 */
class TestComponent extends AbstractComponent
{
    #[\Override]
    public function getName(): string
    {
        return 'TEST';
    }
}