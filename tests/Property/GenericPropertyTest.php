<?php

declare(strict_types=1);

namespace Icalendar\Tests\Property;

use Icalendar\Property\GenericProperty;
use Icalendar\Value\ValueInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class GenericPropertyTest extends TestCase
{
    public function testConstructor(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('SUMMARY', $value);

        $this->assertEquals('SUMMARY', $property->getName());
        $this->assertSame($value, $property->getValue());
    }

    public function testGetName(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('LOCATION', $value);

        $this->assertEquals('LOCATION', $property->getName());
    }

    public function testGetValue(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('DESCRIPTION', $value);

        $this->assertSame($value, $property->getValue());
    }

    public function testSetValue(): void
    {
        $value1 = $this->createMock(ValueInterface::class);
        $value2 = $this->createMock(ValueInterface::class);

        $property = new GenericProperty('SUMMARY', $value1);
        $property->setValue($value2);

        $this->assertSame($value2, $property->getValue());
    }

    public function testGetParameterReturnsExistingValue(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('SUMMARY', $value);
        $property->setParameter('LANGUAGE', 'en-US');

        $this->assertEquals('en-US', $property->getParameter('LANGUAGE'));
    }

    public function testGetParameterReturnsNullForMissingParameter(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('SUMMARY', $value);

        $this->assertNull($property->getParameter('NONEXISTENT'));
    }

    public function testGetParametersReturnsAllParameters(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('SUMMARY', $value);
        $property->setParameter('LANGUAGE', 'en-US');
        $property->setParameter('ENCODING', 'QUOTED-PRINTABLE');

        $parameters = $property->getParameters();
        $this->assertEquals([
            'LANGUAGE' => 'en-US',
            'ENCODING' => 'QUOTED-PRINTABLE',
        ], $parameters);
    }

    public function testSetParameter(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('SUMMARY', $value);
        $property->setParameter('CN', 'John Doe');

        $this->assertEquals('John Doe', $property->getParameter('CN'));
    }

    public function testSetParameterOverwritesExisting(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('SUMMARY', $value);
        $property->setParameter('LANGUAGE', 'en-US');
        $property->setParameter('LANGUAGE', 'fr-FR');

        $this->assertEquals('fr-FR', $property->getParameter('LANGUAGE'));
        $this->assertCount(1, $property->getParameters());
    }

    public function testRemoveParameter(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('SUMMARY', $value);
        $property->setParameter('LANGUAGE', 'en-US');
        $property->setParameter('ENCODING', 'QUOTED-PRINTABLE');

        $property->removeParameter('LANGUAGE');

        $this->assertNull($property->getParameter('LANGUAGE'));
        $this->assertEquals(['ENCODING' => 'QUOTED-PRINTABLE'], $property->getParameters());
    }

    public function testRemoveParameterDoesNothingForMissingParameter(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('SUMMARY', $value);
        $property->setParameter('LANGUAGE', 'en-US');

        $property->removeParameter('NONEXISTENT');

        $this->assertEquals('en-US', $property->getParameter('LANGUAGE'));
        $this->assertCount(1, $property->getParameters());
    }

    public function testMultipleParameters(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('ATTENDEE', $value);
        $property->setParameter('CN', 'John Doe');
        $property->setParameter('PARTSTAT', 'ACCEPTED');
        $property->setParameter('RSVP', 'TRUE');

        $this->assertEquals('John Doe', $property->getParameter('CN'));
        $this->assertEquals('ACCEPTED', $property->getParameter('PARTSTAT'));
        $this->assertEquals('TRUE', $property->getParameter('RSVP'));

        $parameters = $property->getParameters();
        $this->assertEquals([
            'CN' => 'John Doe',
            'PARTSTAT' => 'ACCEPTED',
            'RSVP' => 'TRUE',
        ], $parameters);
    }

    public function testWithDifferentPropertyNames(): void
    {
        $value = $this->createMock(ValueInterface::class);

        $properties = [
            'DTSTART',
            'DTEND',
            'UID',
            'CREATED',
            'LAST-MODIFIED',
            'X-CUSTOM-PROPERTY',
        ];

        foreach ($properties as $name) {
            $property = new GenericProperty($name, $value);
            $this->assertEquals($name, $property->getName());
        }
    }

    public function testWithComplexParameterValues(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $property = new GenericProperty('ATTACH', $value);

        $property->setParameter('FMTTYPE', 'image/png');
        $property->setParameter('ENCODING', 'BASE64');
        $property->setParameter('VALUE', 'BINARY');

        $this->assertEquals('image/png', $property->getParameter('FMTTYPE'));
        $this->assertEquals('BASE64', $property->getParameter('ENCODING'));
        $this->assertEquals('BINARY', $property->getParameter('VALUE'));
    }
}