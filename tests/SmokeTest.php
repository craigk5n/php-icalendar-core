<?php

declare(strict_types=1);

namespace Icalendar\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Smoke test to verify test infrastructure works
 */
class SmokeTest extends TestCase
{
    public function testPhpUnitRuns(): void
    {
        $this->assertTrue(true);
    }

    public function testAutoloadingWorks(): void
    {
        $parser = new \Icalendar\Parser\Parser();
        $this->assertInstanceOf(\Icalendar\Parser\ParserInterface::class, $parser);

        $writer = new \Icalendar\Writer\Writer();
        $this->assertInstanceOf(\Icalendar\Writer\WriterInterface::class, $writer);

        $calendar = new \Icalendar\Component\VCalendar();
        $this->assertInstanceOf(\Icalendar\Component\ComponentInterface::class, $calendar);
    }

    public function testExceptionsExist(): void
    {
        $this->assertTrue(class_exists(\Icalendar\Exception\ParseException::class));
        $this->assertTrue(class_exists(\Icalendar\Exception\ValidationException::class));
        $this->assertTrue(class_exists(\Icalendar\Exception\InvalidDataException::class));
    }

    public function testInterfacesExist(): void
    {
        $this->assertTrue(interface_exists(\Icalendar\Parser\ParserInterface::class));
        $this->assertTrue(interface_exists(\Icalendar\Writer\WriterInterface::class));
        $this->assertTrue(interface_exists(\Icalendar\Component\ComponentInterface::class));
        $this->assertTrue(interface_exists(\Icalendar\Property\PropertyInterface::class));
        $this->assertTrue(interface_exists(\Icalendar\Value\ValueInterface::class));
        $this->assertTrue(interface_exists(\Icalendar\Parser\ValueParser\ValueParserInterface::class));
    }

    public function testErrorSeverityEnumExists(): void
    {
        $this->assertTrue(enum_exists(\Icalendar\Validation\ErrorSeverity::class));
    }
}
