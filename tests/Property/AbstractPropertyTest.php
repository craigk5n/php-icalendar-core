<?php

declare(strict_types=1);

namespace Icalendar\Tests\Property;

use Icalendar\Property\AbstractProperty;
use Icalendar\Value\ValueInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AbstractPropertyTest extends TestCase
{
    private TestProperty $property;

    #[\Override]
    protected function setUp(): void
    {
        $this->property = new TestProperty();
    }

    public function testGetNameReturnsPropertyName(): void
    {
        $this->assertEquals('TEST', $this->property->getName());
    }

    public function testGetValue(): void
    {
        $value = $this->createMock(ValueInterface::class);
        $this->property->setValue($value);

        $this->assertSame($value, $this->property->getValue());
    }

    public function testSetValue(): void
    {
        $value = $this->createMock(ValueInterface::class);

        $this->property->setValue($value);

        $this->assertSame($value, $this->property->getValue());
    }

    public function testGetParameterReturnsExistingValue(): void
    {
        $this->property->setParameter('LANGUAGE', 'en-US');

        $this->assertEquals('en-US', $this->property->getParameter('LANGUAGE'));
    }

    public function testGetParameterReturnsNullForMissingParameter(): void
    {
        $this->assertNull($this->property->getParameter('NONEXISTENT'));
    }

    public function testGetParametersReturnsAllParameters(): void
    {
        $this->property->setParameter('LANGUAGE', 'en-US');
        $this->property->setParameter('ENCODING', 'QUOTED-PRINTABLE');

        $parameters = $this->property->getParameters();
        $this->assertEquals([
            'LANGUAGE' => 'en-US',
            'ENCODING' => 'QUOTED-PRINTABLE',
        ], $parameters);
    }

    public function testGetParametersReturnsEmptyArrayInitially(): void
    {
        $parameters = $this->property->getParameters();
        $this->assertEmpty($parameters);
    }

    public function testSetParameter(): void
    {
        $this->property->setParameter('LANGUAGE', 'en-US');

        $this->assertEquals('en-US', $this->property->getParameter('LANGUAGE'));
    }

    public function testSetParameterOverwritesExisting(): void
    {
        $this->property->setParameter('LANGUAGE', 'en-US');
        $this->property->setParameter('LANGUAGE', 'fr-FR');

        $this->assertEquals('fr-FR', $this->property->getParameter('LANGUAGE'));
        $this->assertCount(1, $this->property->getParameters());
    }

    public function testRemoveParameter(): void
    {
        $this->property->setParameter('LANGUAGE', 'en-US');
        $this->property->setParameter('ENCODING', 'QUOTED-PRINTABLE');

        $this->property->removeParameter('LANGUAGE');

        $this->assertNull($this->property->getParameter('LANGUAGE'));
        $this->assertEquals(['ENCODING' => 'QUOTED-PRINTABLE'], $this->property->getParameters());
    }

    public function testRemoveParameterDoesNothingForMissingParameter(): void
    {
        $this->property->setParameter('LANGUAGE', 'en-US');

        $this->property->removeParameter('NONEXISTENT');

        $this->assertEquals('en-US', $this->property->getParameter('LANGUAGE'));
        $this->assertCount(1, $this->property->getParameters());
    }

    public function testMultipleParameters(): void
    {
        $this->property->setParameter('CN', 'John Doe');
        $this->property->setParameter('PARTSTAT', 'ACCEPTED');
        $this->property->setParameter('RSVP', 'TRUE');

        $this->assertEquals('John Doe', $this->property->getParameter('CN'));
        $this->assertEquals('ACCEPTED', $this->property->getParameter('PARTSTAT'));
        $this->assertEquals('TRUE', $this->property->getParameter('RSVP'));

        $parameters = $this->property->getParameters();
        $this->assertEquals([
            'CN' => 'John Doe',
            'PARTSTAT' => 'ACCEPTED',
            'RSVP' => 'TRUE',
        ], $parameters);
    }
}

/**
 * Concrete implementation for testing AbstractProperty
 */
class TestProperty extends AbstractProperty
{
    #[\Override]
    public function getName(): string
    {
        return 'TEST';
    }
}