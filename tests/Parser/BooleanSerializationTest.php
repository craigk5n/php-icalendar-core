<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser;

use Icalendar\Parser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Parsed BOOLEAN values must be stored as TRUE or FALSE.
 *
 * Parser::formatParsedValue() serialised every scalar with a plain (string)
 * cast. For booleans PHP renders that as '1' and '' -- so TRUE became '1', and
 * FALSE became the empty string, destroying the value outright. RFC 5545 §3.3.2
 * spells them TRUE and FALSE.
 *
 * This covers what the parser stores. Writing those values back is the writers'
 * side of the same defect; see ParsedValueRoundTripTest.
 */
class BooleanSerializationTest extends TestCase
{
    private function calendarWith(string $line): string
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:test-uid\r\nDTSTAMP:20240101T000000Z\r\n"
            . "{$line}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    }

    private function parseValue(string $line, string $property): ?string
    {
        $calendar = (new Parser(Parser::STRICT))->parse($this->calendarWith($line));

        return $calendar->getComponents()[0]->getProperty($property)?->getValue()->getRawValue();
    }

    /** @return array<string, array{string, string}> */
    public static function booleanProvider(): array
    {
        return [
            'RSVP true' => ['RSVP:TRUE', 'TRUE'],
            'RSVP false' => ['RSVP:FALSE', 'FALSE'],
            'extension true' => ['X-CUSTOM;VALUE=BOOLEAN:TRUE', 'TRUE'],
            'extension false' => ['X-CUSTOM;VALUE=BOOLEAN:FALSE', 'FALSE'],
        ];
    }

    #[DataProvider('booleanProvider')]
    public function testBooleanIsStoredCanonically(string $line, string $expected): void
    {
        $property = strtok($line, ':;');

        $this->assertSame($expected, $this->parseValue($line, (string) $property));
    }

    /** Regression: FALSE cast to '' destroyed the value. */
    public function testFalseIsNotDestroyed(): void
    {
        $raw = $this->parseValue('RSVP:FALSE', 'RSVP');

        $this->assertNotSame('', $raw, 'RSVP:FALSE was cast to an empty string');
        $this->assertSame('FALSE', $raw);
    }

    /** Regression: TRUE must not be stored as the PHP cast '1'. */
    public function testTrueIsNotStoredAsOne(): void
    {
        $raw = $this->parseValue('RSVP:TRUE', 'RSVP');

        $this->assertNotSame('1', $raw, "RSVP:TRUE was stored as the PHP cast '1'");
        $this->assertSame('TRUE', $raw);
    }

    /** BOOLEAN is case-insensitive on input (§3.3.2); storage is canonical. */
    public function testLowercaseInputIsStoredCanonically(): void
    {
        $this->assertSame('TRUE', $this->parseValue('RSVP:true', 'RSVP'));
        $this->assertSame('FALSE', $this->parseValue('RSVP:false', 'RSVP'));
    }

    /** Other scalar types must be unaffected by the boolean branch. */
    public function testNonBooleanScalarsAreUnchanged(): void
    {
        $this->assertSame('5', $this->parseValue('SEQUENCE:5', 'SEQUENCE'));
        $this->assertSame('0', $this->parseValue('SEQUENCE:0', 'SEQUENCE'));
        $this->assertSame('hello', $this->parseValue('SUMMARY:hello', 'SUMMARY'));
    }
}
