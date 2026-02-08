<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use DateInterval;
use DateTimeImmutable;
use Icalendar\Recurrence\RRule;
use Icalendar\Writer\ValueWriter\BinaryWriter;
use Icalendar\Writer\ValueWriter\BooleanWriter;
use Icalendar\Writer\ValueWriter\CalAddressWriter;
use Icalendar\Writer\ValueWriter\DateTimeWriter;
use Icalendar\Writer\ValueWriter\DateWriter;
use Icalendar\Writer\ValueWriter\DurationWriter;
use Icalendar\Writer\ValueWriter\FloatWriter;
use Icalendar\Writer\ValueWriter\IntegerWriter;
use Icalendar\Writer\ValueWriter\PeriodWriter;
use Icalendar\Writer\ValueWriter\RecurWriter;
use Icalendar\Writer\ValueWriter\TextWriter;
use Icalendar\Writer\ValueWriter\TimeWriter;
use Icalendar\Writer\ValueWriter\UriWriter;
use Icalendar\Writer\ValueWriter\UtcOffsetWriter;
use Icalendar\Writer\ValueWriter\ValueWriterFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Value Writers
 */
class ValueWriterTest extends TestCase
{
    /** @test */
    public function testWriteDate(): void
    {
        $writer = new DateWriter();
        $date = new DateTimeImmutable('2026-02-15');

        $this->assertEquals('20260215', $writer->write($date));
    }

    /** @test */
    public function testWriteDateTimeUtc(): void
    {
        $writer = new DateTimeWriter();
        $dateTime = new DateTimeImmutable('2026-02-15T14:30:00', new \DateTimeZone('UTC'));

        $this->assertEquals('20260215T143000Z', $writer->write($dateTime));
    }

    /** @test */
    public function testWriteDateTimeLocal(): void
    {
        $writer = new DateTimeWriter();
        $dateTime = new DateTimeImmutable('2026-02-15T14:30:00', new \DateTimeZone('America/New_York'));

        $this->assertEquals('20260215T143000', $writer->write($dateTime));
    }

    /** @test */
    public function testWriteTextEscaping(): void
    {
        $writer = new TextWriter();

        // Test backslash escaping
        $this->assertEquals('\\\\', $writer->write('\\'));

        // Test semicolon escaping
        $this->assertEquals('\\;', $writer->write(';'));

        // Test comma escaping
        $this->assertEquals('\\,', $writer->write(','));

        // Test newline escaping
        $this->assertEquals('\\n', $writer->write("\n"));

        // Test carriage return removal
        $this->assertEquals('', $writer->write("\r"));

        // Test combined escaping
        $this->assertEquals('Hello\\, world\\; \\\\', $writer->write('Hello, world; \\'));
    }

    /** @test */
    public function testWriteDuration(): void
    {
        $writer = new DurationWriter();

        // Weeks only
        $interval = new DateInterval('P2W');
        $this->assertEquals('P2W', $writer->write($interval));

        // Days and time
        $interval = new DateInterval('P1DT2H3M4S');
        $this->assertEquals('P1DT2H3M4S', $writer->write($interval));

        // Negative duration
        $interval = new DateInterval('PT1H');
        $interval->invert = 1;
        $this->assertEquals('-PT1H', $writer->write($interval));
    }

    /** @test */
    public function testWriteInteger(): void
    {
        $writer = new IntegerWriter();

        $this->assertEquals('42', $writer->write(42));
        $this->assertEquals('-10', $writer->write(-10));
        $this->assertEquals('0', $writer->write(0));
    }

    /** @test */
    public function testWriteFloat(): void
    {
        $writer = new FloatWriter();

        $this->assertEquals('3.14', $writer->write(3.14));
        $this->assertEquals('-2.5', $writer->write(-2.5));
        $this->assertEquals('0', $writer->write(0.0));
    }

    /** @test */
    public function testWriteBoolean(): void
    {
        $writer = new BooleanWriter();

        $this->assertEquals('TRUE', $writer->write(true));
        $this->assertEquals('FALSE', $writer->write(false));
    }

    /** @test */
    public function testWriteUri(): void
    {
        $writer = new UriWriter();

        $this->assertEquals('https://example.com', $writer->write('https://example.com'));
        $this->assertEquals('mailto:test@example.com', $writer->write('mailto:test@example.com'));
    }

    /** @test */
    public function testWriteTime(): void
    {
        $writer = new TimeWriter();

        // Local time
        $time = new DateTimeImmutable('2026-02-15T14:30:45', new \DateTimeZone('America/New_York'));
        $this->assertEquals('143045', $writer->write($time));

        // UTC time
        $time = new DateTimeImmutable('2026-02-15T14:30:45', new \DateTimeZone('UTC'));
        $this->assertEquals('143045Z', $writer->write($time));
    }

    /** @test */
    public function testWriteUtcOffset(): void
    {
        $writer = new UtcOffsetWriter();

        // Positive offset
        $this->assertEquals('+0530', $writer->write(19800)); // +5:30

        // Negative offset
        $this->assertEquals('-0500', $writer->write(-18000)); // -5:00

        // With seconds
        $this->assertEquals('+053045', $writer->write(19845)); // +5:30:45

        // Zero offset
        $this->assertEquals('+0000', $writer->write(0));
    }

    /** @test */
    public function testWriteBinary(): void
    {
        $writer = new BinaryWriter();
        $binary = "Hello\x00World\xFF\xFE";

        $result = $writer->write($binary);

        // Should be base64 encoded
        $this->assertEquals(base64_encode($binary), $result);
    }

    /** @test */
    public function testWriteCalAddress(): void
    {
        $writer = new CalAddressWriter();

        // Already has mailto:
        $this->assertEquals('mailto:test@example.com', $writer->write('mailto:test@example.com'));

        // Without mailto:
        $this->assertEquals('mailto:test@example.com', $writer->write('test@example.com'));
    }

    /** @test */
    public function testWritePeriodWithEnd(): void
    {
        $writer = new PeriodWriter();

        $start = new DateTimeImmutable('2026-02-15T10:00:00', new \DateTimeZone('UTC'));
        $end = new DateTimeImmutable('2026-02-15T11:00:00', new \DateTimeZone('UTC'));

        $result = $writer->write(['start' => $start, 'end' => $end]);

        $this->assertEquals('20260215T100000Z/20260215T110000Z', $result);
    }

    /** @test */
    public function testWritePeriodWithDuration(): void
    {
        $writer = new PeriodWriter();

        $start = new DateTimeImmutable('2026-02-15T10:00:00', new \DateTimeZone('UTC'));
        $duration = new DateInterval('PT1H');

        $result = $writer->write(['start' => $start, 'duration' => $duration]);

        $this->assertEquals('20260215T100000Z/PT1H', $result);
    }

    /** @test */
    public function testWriteRecur(): void
    {
        $writer = new RecurWriter();
        $parser = new \Icalendar\Recurrence\RRuleParser();

        $rrule = $parser->parse('FREQ=DAILY;COUNT=10');

        $this->assertEquals('FREQ=DAILY;COUNT=10', $writer->write($rrule));
    }

    /** @test */
    public function testFactoryReturnsCorrectWriter(): void
    {
        $factory = new ValueWriterFactory();

        $this->assertInstanceOf(DateWriter::class, $factory->getWriter('DATE'));
        $this->assertInstanceOf(TextWriter::class, $factory->getWriter('TEXT'));
        $this->assertInstanceOf(IntegerWriter::class, $factory->getWriter('INTEGER'));
    }

    /** @test */
    public function testFactoryWriteValue(): void
    {
        $factory = new ValueWriterFactory();
        $date = new DateTimeImmutable('2026-02-15');

        $this->assertEquals('20260215', $factory->write($date, 'DATE'));
    }

    /** @test */
    public function testFactoryHasWriter(): void
    {
        $factory = new ValueWriterFactory();

        $this->assertTrue($factory->hasWriter('DATE'));
        $this->assertTrue($factory->hasWriter('date')); // case insensitive
        $this->assertFalse($factory->hasWriter('UNKNOWN'));
    }

    /** @test */
    public function testFactoryGetSupportedTypes(): void
    {
        $factory = new ValueWriterFactory();
        $types = $factory->getSupportedTypes();

        $this->assertContains('DATE', $types);
        $this->assertContains('DATE-TIME', $types);
        $this->assertContains('TEXT', $types);
        $this->assertContains('DURATION', $types);
    }

    /** @test */
    public function testFactoryThrowsForUnknownType(): void
    {
        $factory = new ValueWriterFactory();

        $this->expectException(\Icalendar\Exception\InvalidDataException::class);
        $factory->getWriter('UNKNOWN_TYPE');
    }

    /** @test */
    public function testFactoryRegisterCustomWriter(): void
    {
        $factory = new ValueWriterFactory();

        $customWriter = new class implements \Icalendar\Writer\ValueWriter\ValueWriterInterface {
            #[\Override]
            public function write(mixed $value): string
            {
                return 'CUSTOM:' . $value;
            }

            #[\Override]
            public function getType(): string
            {
                return 'CUSTOM';
            }

            #[\Override]
            public function canWrite(mixed $value): bool
            {
                return is_string($value);
            }
        };

        $factory->registerWriter('CUSTOM', $customWriter);

        $this->assertTrue($factory->hasWriter('CUSTOM'));
        $this->assertEquals('CUSTOM:test', $factory->write('test', 'CUSTOM'));
    }

    /** @test */
    public function testAllWriters(): void
    {
        $factory = new ValueWriterFactory();

        // Test all supported types
        $testCases = [
            ['type' => 'DATE', 'value' => new DateTimeImmutable('2026-02-15'), 'expected' => '20260215'],
            ['type' => 'DATE-TIME', 'value' => new DateTimeImmutable('2026-02-15T10:30:00', new \DateTimeZone('UTC')), 'expected' => '20260215T103000Z'],
            ['type' => 'TEXT', 'value' => 'Hello, world!', 'expected' => 'Hello\\, world!'],
            ['type' => 'INTEGER', 'value' => 42, 'expected' => '42'],
            ['type' => 'FLOAT', 'value' => 3.14, 'expected' => '3.14'],
            ['type' => 'BOOLEAN', 'value' => true, 'expected' => 'TRUE'],
            ['type' => 'URI', 'value' => 'https://example.com', 'expected' => 'https://example.com'],
            ['type' => 'TIME', 'value' => new DateTimeImmutable('2026-02-15T10:30:00', new \DateTimeZone('America/New_York')), 'expected' => '103000'],
            ['type' => 'UTC-OFFSET', 'value' => -18000, 'expected' => '-0500'],
            ['type' => 'CAL-ADDRESS', 'value' => 'test@example.com', 'expected' => 'mailto:test@example.com'],
        ];

        foreach ($testCases as $case) {
            $result = $factory->write($case['value'], $case['type']);
            $this->assertEquals($case['expected'], $result, "Failed for type: {$case['type']}");
        }
    }
}