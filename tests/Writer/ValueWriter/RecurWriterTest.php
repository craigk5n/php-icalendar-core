<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Recurrence\RRule;
use Icalendar\Writer\ValueWriter\RecurWriter;
use PHPUnit\Framework\TestCase;

class RecurWriterTest extends TestCase
{
    private RecurWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new RecurWriter();
    }

    // ========== write Tests ==========

    public function testWriteSimpleDaily(): void
    {
        $rrule = RRule::parse('FREQ=DAILY');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=DAILY', $result);
    }

    public function testWriteWithInterval(): void
    {
        $rrule = RRule::parse('FREQ=WEEKLY;INTERVAL=2');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=WEEKLY;INTERVAL=2', $result);
    }

    public function testWriteWithCount(): void
    {
        $rrule = RRule::parse('FREQ=DAILY;COUNT=10');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=DAILY;COUNT=10', $result);
    }

    public function testWriteWithUntil(): void
    {
        $rrule = RRule::parse('FREQ=DAILY;UNTIL=20261231T235959Z');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=DAILY;UNTIL=20261231T235959Z', $result);
    }

    public function testWriteWithByDay(): void
    {
        $rrule = RRule::parse('FREQ=WEEKLY;BYDAY=MO,WE,FR');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=WEEKLY;BYDAY=MO,WE,FR', $result);
    }

    public function testWriteWithByDayOrdinal(): void
    {
        $rrule = RRule::parse('FREQ=MONTHLY;BYDAY=2MO');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=MONTHLY;BYDAY=2MO', $result);
    }

    public function testWriteComplexRrule(): void
    {
        $rrule = RRule::parse('FREQ=YEARLY;INTERVAL=2;BYMONTH=3,6,9;BYDAY=2SU;UNTIL=20301231T235959Z');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=YEARLY;INTERVAL=2;BYMONTH=3,6,9;BYDAY=2SU;UNTIL=20301231T235959Z', $result);
    }

    public function testWriteWithWkst(): void
    {
        $rrule = RRule::parse('FREQ=WEEKLY;WKST=SU');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=WEEKLY;WKST=SU', $result);
    }

    public function testWriteWithMultipleByParameters(): void
    {
        $rrule = RRule::parse('FREQ=MONTHLY;BYMONTHDAY=1,15;BYHOUR=9,17;BYMINUTE=0,30');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=MONTHLY;BYMONTHDAY=1,15;BYHOUR=9,17;BYMINUTE=0,30', $result);
    }

    public function testWriteWithBySetPos(): void
    {
        $rrule = RRule::parse('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1', $result);
    }

    public function testWriteHourlyRrule(): void
    {
        $rrule = RRule::parse('FREQ=HOURLY');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=HOURLY', $result);
    }

    public function testWriteMinutelyRrule(): void
    {
        $rrule = RRule::parse('FREQ=MINUTELY;INTERVAL=15');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=MINUTELY;INTERVAL=15', $result);
    }

    public function testWriteSecondlyRrule(): void
    {
        $rrule = RRule::parse('FREQ=SECONDLY;COUNT=30');
        $result = $this->writer->write($rrule);
        
        $this->assertEquals('FREQ=SECONDLY;COUNT=30', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('RecurWriter expects RRule, got string');
        
        $this->writer->write('FREQ=DAILY');
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('RecurWriter expects RRule, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('RecurWriter expects RRule, got array');
        
        $this->writer->write(['FREQ' => 'DAILY']);
    }

    public function testWriteInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('RecurWriter expects RRule, got integer');
        
        $this->writer->write(123);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('RECUR', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $rrule = RRule::parse('FREQ=DAILY');
        $mockRrule = $this->createMock(RRule::class);
        
        $this->assertTrue($this->writer->canWrite($rrule));
        $this->assertTrue($this->writer->canWrite($mockRrule));
        
        $this->assertFalse($this->writer->canWrite('FREQ=DAILY'));
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(123));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
    }

    // ========== Integration Tests ==========

    public function testWriteRoundTrip(): void
    {
        $originalStrings = [
            'FREQ=DAILY',
            'FREQ=WEEKLY;INTERVAL=2',
            'FREQ=MONTHLY;BYDAY=1MO',
            'FREQ=YEARLY;BYMONTH=12;BYDAY=25WE',
            'FREQ=DAILY;COUNT=10;BYHOUR=9',
            'FREQ=HOURLY;UNTIL=20261231T235959Z',
        ];
        
        foreach ($originalStrings as $original) {
            $rrule = new RRule($original);
            $result = $this->writer->write($rrule);
            
            $this->assertEquals($original, $result, "Round trip failed for: $original");
        }
    }

    public function testWriteProducesValidRruleFormat(): void
    {
        $testCases = [
            ['FREQ=DAILY', '/^FREQ=DAILY$/'],
            ['FREQ=WEEKLY;INTERVAL=2', '/^FREQ=WEEKLY;INTERVAL=2$/'],
            ['FREQ=MONTHLY;BYDAY=MO,WE,FR', '/^FREQ=MONTHLY;BYDAY=MO,WE,FR$/'],
            ['FREQ=YEARLY;UNTIL=20261231T235959Z', '/^FREQ=YEARLY;UNTIL=\d{8}T\d{6}Z$/'],
        ];
        
        foreach ($testCases as [$rruleString, $pattern]) {
            $rrule = new RRule($rruleString);
            $result = $this->writer->write($rrule);
            
            $this->assertMatchesRegularExpression($pattern, $result);
        }
    }

    public function testWritePreservesCase(): void
    {
        $rrule = RRule::parse('FREQ=DAILY;WKST=SU;BYDAY=MO,TU,WE,TH,FR,SA,SU');
        $result = $this->writer->write($rrule);
        
        // RRule should preserve the standard uppercase format
        $this->assertStringContainsString('FREQ=', $result);
        $this->assertStringContainsString('WKST=', $result);
        $this->assertStringContainsString('BYDAY=', $result);
        $this->assertStringNotContainsString('freq=', $result);
        $this->assertStringNotContainsString('wkst=', $result);
        $this->assertStringNotContainsString('byday=', $result);
    }

    public function testWriteAllFrequencies(): void
    {
        $frequencies = ['SECONDLY', 'MINUTELY', 'HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'];
        
        foreach ($frequencies as $freq) {
            $rrule = RRule::parse("FREQ=$freq");
            $result = $this->writer->write($rrule);
            
            $this->assertEquals("FREQ=$freq", $result);
        }
    }
}