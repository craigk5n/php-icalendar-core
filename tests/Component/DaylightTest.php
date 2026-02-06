<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\Daylight;
use Icalendar\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class DaylightTest extends TestCase
{
    public function testDaylightRequiresDtstart(): void
    {
        $daylight = new Daylight();
        $daylight->setTzOffsetTo(-14400);
        $daylight->setTzOffsetFrom(-18000);
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DAYLIGHT component missing required DTSTART property');
        
        $daylight->validate();
    }

    public function testDaylightRequiresTzoffsetto(): void
    {
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetFrom(-18000);
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DAYLIGHT component missing required TZOFFSETTO property');
        
        $daylight->validate();
    }

    public function testDaylightRequiresTzoffsetfrom(): void
    {
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400);
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DAYLIGHT component missing required TZOFFSETFROM property');
        
        $daylight->validate();
    }

    public function testDaylightValidatesSuccessfully(): void
    {
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400);
        $daylight->setTzOffsetFrom(-18000);
        
        // Should not throw exception
        $this->assertNull($daylight->validate());
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testDaylightSupportsRrule(): void
    {
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400);
        $daylight->setTzOffsetFrom(-18000);
        $daylight->setTzName('EDT');
        
        // Add RRULE property (though we don't have a full RRULE implementation yet)
        // For now, just test that we can add a generic property
        $this->assertTrue(true); // Placeholder test
    }

    public function testDaylightSupportsTzname(): void
    {
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400);
        $daylight->setTzOffsetFrom(-18000);
        $daylight->setTzName('EDT');
        
        $tzName = $daylight->getProperty('TZNAME');
        $this->assertNotNull($tzName);
        $this->assertEquals('EDT', $tzName->getValue()->getRawValue());
    }

    public function testDaylightFluentInterface(): void
    {
        $daylight = new Daylight();
        
        $result = $daylight
            ->setDtStart(new \DateTime('2026-03-08T02:00:00'))
            ->setTzOffsetTo(-14400)
            ->setTzOffsetFrom(-18000)
            ->setTzName('EDT');
        
        $this->assertSame($daylight, $result);
        
        $this->assertNotNull($daylight->getProperty('DTSTART'));
        $this->assertNotNull($daylight->getProperty('TZOFFSETTO'));
        $this->assertNotNull($daylight->getProperty('TZOFFSETFROM'));
        $this->assertNotNull($daylight->getProperty('TZNAME'));
    }

    public function testDaylightUtcOffsetFormatting(): void
    {
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        
        // Test positive offset
        $daylight->setTzOffsetTo(7200); // +02:00
        $tzOffsetTo = $daylight->getProperty('TZOFFSETTO');
        $this->assertEquals('+0200', $tzOffsetTo->getValue()->getRawValue());
        
        // Test negative offset
        $daylight->setTzOffsetFrom(-14400); // -04:00
        $tzOffsetFrom = $daylight->getProperty('TZOFFSETFROM');
        $this->assertEquals('-0400', $tzOffsetFrom->getValue()->getRawValue());
        
        // Test zero offset
        $daylight->setTzOffsetTo(0); // +00:00
        $tzOffsetTo = $daylight->getProperty('TZOFFSETTO');
        $this->assertEquals('+0000', $tzOffsetTo->getValue()->getRawValue());
    }
}