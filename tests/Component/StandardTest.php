<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\Standard;
use Icalendar\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class StandardTest extends TestCase
{
    public function testStandardRequiresDtstart(): void
    {
        $standard = new Standard();
        $standard->setTzOffsetTo(-18000);
        $standard->setTzOffsetFrom(-14400);
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('STANDARD component missing required DTSTART property');
        
        $standard->validate();
    }

    public function testStandardRequiresTzoffsetto(): void
    {
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        $standard->setTzOffsetFrom(-14400);
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('STANDARD component missing required TZOFFSETTO property');
        
        $standard->validate();
    }

    public function testStandardRequiresTzoffsetfrom(): void
    {
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        $standard->setTzOffsetTo(-18000);
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('STANDARD component missing required TZOFFSETFROM property');
        
        $standard->validate();
    }

    public function testStandardValidatesSuccessfully(): void
    {
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        $standard->setTzOffsetTo(-18000);
        $standard->setTzOffsetFrom(-14400);
        
        // Should not throw exception
        $this->assertNull($standard->validate());
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testStandardFluentInterface(): void
    {
        $standard = new Standard();
        
        $result = $standard
            ->setDtStart(new \DateTime('2026-11-01T02:00:00'))
            ->setTzOffsetTo(-18000)
            ->setTzOffsetFrom(-14400)
            ->setTzName('EST');
        
        $this->assertSame($standard, $result);
        
        $this->assertNotNull($standard->getProperty('DTSTART'));
        $this->assertNotNull($standard->getProperty('TZOFFSETTO'));
        $this->assertNotNull($standard->getProperty('TZOFFSETFROM'));
        $this->assertNotNull($standard->getProperty('TZNAME'));
    }

    public function testStandardUtcOffsetFormatting(): void
    {
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        
        // Test positive offset
        $standard->setTzOffsetTo(3600); // +01:00
        $tzOffsetTo = $standard->getProperty('TZOFFSETTO');
        $this->assertEquals('+0100', $tzOffsetTo->getValue()->getRawValue());
        
        // Test negative offset
        $standard->setTzOffsetFrom(-18000); // -05:00
        $tzOffsetFrom = $standard->getProperty('TZOFFSETFROM');
        $this->assertEquals('-0500', $tzOffsetFrom->getValue()->getRawValue());
        
        // Test offset with seconds
        $standard->setTzOffsetTo(19800); // +05:30:00
        $tzOffsetTo = $standard->getProperty('TZOFFSETTO');
        $this->assertEquals('+0530', $tzOffsetTo->getValue()->getRawValue());
        
        // Test offset with seconds
        $standard->setTzOffsetTo(19861); // +05:30:61
        $tzOffsetTo = $standard->getProperty('TZOFFSETTO');
        $this->assertEquals('+053101', $tzOffsetTo->getValue()->getRawValue());
    }
}