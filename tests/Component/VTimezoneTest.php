<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\VTimezone;
use Icalendar\Component\Standard;
use Icalendar\Component\Daylight;
use Icalendar\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class VTimezoneTest extends TestCase
{
    public function testVTimezoneRequiresTzid(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('VTIMEZONE component missing required TZID property');
        
        $timezone = new VTimezone();
        $timezone->validate();
    }

    public function testVTimezoneRequiresObservance(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('VTIMEZONE component requires at least one STANDARD or DAYLIGHT sub-component');
        
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        $timezone->validate();
    }

    public function testVTimezoneAcceptsStandard(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        $standard->setTzOffsetTo(-18000); // -05:00
        $standard->setTzOffsetFrom(-14400); // -04:00
        $standard->setTzName('EST');
        
        $timezone->addStandard($standard);
        
        $this->assertCount(1, $timezone->getComponents('STANDARD'));
        $this->assertInstanceOf(Standard::class, $timezone->getComponents('STANDARD')[0]);
    }

    public function testVTimezoneAcceptsDaylight(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400); // -04:00
        $daylight->setTzOffsetFrom(-18000); // -05:00
        $daylight->setTzName('EDT');
        
        $timezone->addDaylight($daylight);
        
        $this->assertCount(1, $timezone->getComponents('DAYLIGHT'));
        $this->assertInstanceOf(Daylight::class, $timezone->getComponents('DAYLIGHT')[0]);
    }

    public function testVTimezoneBuildTransitionTable(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        
        // Add standard time (fall back)
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        $standard->setTzOffsetTo(-18000); // -05:00 EST
        $standard->setTzOffsetFrom(-14400); // -04:00 EDT
        $standard->setTzName('EST');
        
        // Add daylight time (spring forward)
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400); // -04:00 EDT
        $daylight->setTzOffsetFrom(-18000); // -05:00 EST
        $daylight->setTzName('EDT');
        
        $timezone->addStandard($standard);
        $timezone->addDaylight($daylight);
        
        $timezone->buildTransitions();
        
        $transitions = $timezone->getTransitions();
        $this->assertCount(2, $transitions);
        
        // Transitions should be sorted by time
        $this->assertEquals('2026-03-08T02:00:00', $transitions[0]['time']);
        $this->assertEquals(-14400, $transitions[0]['offset']); // EDT
        $this->assertEquals('EDT', $transitions[0]['name']);
        
        $this->assertEquals('2026-11-01T02:00:00', $transitions[1]['time']);
        $this->assertEquals(-18000, $transitions[1]['offset']); // EST
        $this->assertEquals('EST', $transitions[1]['name']);
    }

    public function testVTimezoneGetOffsetAt(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        
        // Add standard time (fall back)
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        $standard->setTzOffsetTo(-18000); // -05:00 EST
        $standard->setTzOffsetFrom(-14400); // -04:00 EDT
        $standard->setTzName('EST');
        
        // Add daylight time (spring forward)
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400); // -04:00 EDT
        $daylight->setTzOffsetFrom(-18000); // -05:00 EST
        $daylight->setTzName('EDT');
        
        $timezone->addStandard($standard);
        $timezone->addDaylight($daylight);
        
        $timezone->buildTransitions();
        
        // Before any transition - should be 0 (default)
        $marchDt = new \DateTime('2026-03-01T12:00:00');
        $this->assertEquals(0, $timezone->getOffsetAt($marchDt));
        
        // After daylight time starts - should be EDT (-04:00)
        $aprilDt = new \DateTime('2026-04-01T12:00:00');
        $this->assertEquals(-14400, $timezone->getOffsetAt($aprilDt));
        
        // After standard time returns - should be EST (-05:00)
        $novemberDt = new \DateTime('2026-12-01T12:00:00');
        $this->assertEquals(-18000, $timezone->getOffsetAt($novemberDt));
    }

    public function testVTimezoneGetAbbreviationAt(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        
        // Add standard time (fall back)
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        $standard->setTzOffsetTo(-18000); // -05:00 EST
        $standard->setTzOffsetFrom(-14400); // -04:00 EDT
        $standard->setTzName('EST');
        
        // Add daylight time (spring forward)
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400); // -04:00 EDT
        $daylight->setTzOffsetFrom(-18000); // -05:00 EST
        $daylight->setTzName('EDT');
        
        $timezone->addStandard($standard);
        $timezone->addDaylight($daylight);
        
        $timezone->buildTransitions();
        
        // Before any transition - should be UTC (default)
        $marchDt = new \DateTime('2026-03-01T12:00:00');
        $this->assertEquals('UTC', $timezone->getAbbreviationAt($marchDt));
        
        // After daylight time starts - should be EDT
        $aprilDt = new \DateTime('2026-04-01T12:00:00');
        $this->assertEquals('EDT', $timezone->getAbbreviationAt($aprilDt));
        
        // After standard time returns - should be EST
        $novemberDt = new \DateTime('2026-12-01T12:00:00');
        $this->assertEquals('EST', $timezone->getAbbreviationAt($novemberDt));
    }

    public function testVTimezoneHandlesDstSpringForward(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        
        // Add standard time (previous state)
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2025-11-02T02:00:00'));
        $standard->setTzOffsetTo(-18000); // -05:00 (EST)
        $standard->setTzOffsetFrom(-14400); // -04:00 (EDT)
        $standard->setTzName('EST');
        
        // Spring forward: 2:00 AM becomes 3:00 AM, clock jumps forward
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400); // -04:00 (EDT)
        $daylight->setTzOffsetFrom(-18000); // -05:00 (EST)
        $daylight->setTzName('EDT');
        
        $timezone->addStandard($standard);
        $timezone->addDaylight($daylight);
        $timezone->buildTransitions();
        
        // Test time right before transition (should still be in EDT)
        $beforeTransition = new \DateTime('2026-11-01T01:59:59');
        $this->assertEquals(-14400, $timezone->getOffsetAt($beforeTransition));
        $this->assertEquals('EDT', $timezone->getAbbreviationAt($beforeTransition));
        
        // Test time right after transition
        $afterTransition = new \DateTime('2026-03-08T03:00:01');
        $this->assertEquals(-14400, $timezone->getOffsetAt($afterTransition));
        $this->assertEquals('EDT', $timezone->getAbbreviationAt($afterTransition));
    }

    public function testVTimezoneHandlesDstFallBack(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        
        // Add daylight time (previous state)
        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00'));
        $daylight->setTzOffsetTo(-14400); // -04:00 (EDT)
        $daylight->setTzOffsetFrom(-18000); // -05:00 (EST)
        $daylight->setTzName('EDT');
        
        // Fall back: 2:00 AM becomes 1:00 AM, clock goes back
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        $standard->setTzOffsetTo(-18000); // -05:00 (EST)
        $standard->setTzOffsetFrom(-14400); // -04:00 (EDT)
        $standard->setTzName('EST');
        
        $timezone->addDaylight($daylight);
        $timezone->addStandard($standard);
        $timezone->buildTransitions();
        
        // Test time right before transition
        $beforeTransition = new \DateTime('2026-11-01T01:59:59');
        $this->assertEquals(-14400, $timezone->getOffsetAt($beforeTransition));
        $this->assertEquals('EDT', $timezone->getAbbreviationAt($beforeTransition));
        
        // Test time right after transition (the "first" 1:00 AM after falling back)
        // Actually, since the transition is at 02:00 and we fall back to 01:00,
        // 01:00:01 after the transition would be in the next day cycle
        $afterTransition = new \DateTime('2026-11-01T03:00:01'); // After 02:00 transition
        $this->assertEquals(-18000, $timezone->getOffsetAt($afterTransition));
        $this->assertEquals('EST', $timezone->getAbbreviationAt($afterTransition));
    }

    public function testVTimezoneMapsToPhpTimezone(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        
        $phpTimezone = $timezone->toPhpDateTimeZone();
        $this->assertInstanceOf(\DateTimeZone::class, $phpTimezone);
        $this->assertEquals('America/New_York', $phpTimezone->getName());
        
        // Test with invalid timezone
        $timezone->setTzId('Invalid/Timezone');
        $this->assertNull($timezone->toPhpDateTimeZone());
    }

    public function testVTimezoneFluentInterface(): void
    {
        $timezone = new VTimezone();
        
        $result = $timezone
            ->setTzId('America/New_York')
            ->setLastModified(new \DateTime('2026-01-01T00:00:00'))
            ->setTzUrl('http://example.com/timezone');
        
        $this->assertSame($timezone, $result);
        
        $this->assertNotNull($timezone->getProperty('TZID'));
        $this->assertNotNull($timezone->getProperty('LAST-MODIFIED'));
        $this->assertNotNull($timezone->getProperty('TZURL'));
    }

    public function testVTimezoneValidation(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');
        
        // Add valid observance
        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00'));
        $standard->setTzOffsetTo(-18000);
        $standard->setTzOffsetFrom(-14400);
        $standard->setTzName('EST');
        
        $timezone->addStandard($standard);
        
        // Should not throw exception
        $this->assertNull($timezone->validate());
        $this->assertTrue(true); // Test passes if no exception is thrown
    }
}