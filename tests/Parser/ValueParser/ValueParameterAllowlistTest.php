<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\Parser;
use Icalendar\Parser\ValueParser\ValueParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A VALUE parameter may only declare a type the property actually permits.
 *
 * ValueParserFactory::getParserForProperty() consulted the VALUE parameter and
 * never the property name, so any property could be re-typed to anything:
 *
 *   DTSTART;VALUE=TEXT:total-garbage     -> accepted, zero warnings, even STRICT
 *   DTSTART;VALUE=BOOLEAN:TRUE           -> accepted, then crashed the writer
 *   SUMMARY;VALUE=DATE-TIME:...          -> accepted
 *
 * VALUE=TEXT was the worst of these: TextParser cannot fail, so declaring it
 * disabled validation on *any* property. RFC 5545 §3.8 fixes the permitted types
 * per property (§3.8.2.4 allows only DATE-TIME or DATE on DTSTART), so the
 * allowlist is spec-derived rather than invented.
 *
 * Unknown and X- properties keep accepting any declared type: the library cannot
 * know what an extension's value means, and real producers do emit
 * X-APPLE-STRUCTURED-LOCATION;VALUE=URI.
 */
class ValueParameterAllowlistTest extends TestCase
{
    private ValueParserFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new ValueParserFactory();
    }

    /** @return array<string, array{string, string}> */
    public static function illegalValueTypeProvider(): array
    {
        return [
            'DTSTART as TEXT' => ['DTSTART', 'TEXT'],
            'DTSTART as BOOLEAN' => ['DTSTART', 'BOOLEAN'],
            'DTSTART as INTEGER' => ['DTSTART', 'INTEGER'],
            'DTSTART as DURATION' => ['DTSTART', 'DURATION'],
            'DTEND as TEXT' => ['DTEND', 'TEXT'],
            'DTSTAMP as DATE' => ['DTSTAMP', 'DATE'],
            'DTSTAMP as TEXT' => ['DTSTAMP', 'TEXT'],
            'SUMMARY as DATE-TIME' => ['SUMMARY', 'DATE-TIME'],
            'SUMMARY as INTEGER' => ['SUMMARY', 'INTEGER'],
            'UID as BINARY' => ['UID', 'BINARY'],
            'RRULE as TEXT' => ['RRULE', 'TEXT'],
            'ORGANIZER as TEXT' => ['ORGANIZER', 'TEXT'],
            'SEQUENCE as TEXT' => ['SEQUENCE', 'TEXT'],
            'ATTACH as TEXT' => ['ATTACH', 'TEXT'],
            'TRIGGER as TEXT' => ['TRIGGER', 'TEXT'],
        ];
    }

    #[DataProvider('illegalValueTypeProvider')]
    public function testIllegalValueTypeIsRejected(string $property, string $type): void
    {
        $this->expectException(ParseException::class);
        $this->factory->getParserForProperty($property, ['VALUE' => $type]);
    }

    #[DataProvider('illegalValueTypeProvider')]
    public function testIllegalValueTypeUsesTypeDeclarationMismatchCode(string $property, string $type): void
    {
        try {
            $this->factory->getParserForProperty($property, ['VALUE' => $type]);
            $this->fail("{$property};VALUE={$type} should have been rejected");
        } catch (ParseException $e) {
            $this->assertSame(ParseException::ERR_TYPE_DECLARATION_MISMATCH, $e->getErrorCode());
        }
    }

    /**
     * The alternates RFC 5545 actually permits. These are what real calendars
     * emit -- DTSTART;VALUE=DATE alone appears ~2900 times in the fixtures -- so
     * the allowlist must not touch them.
     *
     * @return array<string, array{string, string}>
     */
    public static function legalValueTypeProvider(): array
    {
        return [
            // §3.8.2.4 / §3.8.2.2 / §3.8.2.3
            'DTSTART as DATE' => ['DTSTART', 'DATE'],
            'DTSTART as DATE-TIME' => ['DTSTART', 'DATE-TIME'],
            'DTEND as DATE' => ['DTEND', 'DATE'],
            'DUE as DATE' => ['DUE', 'DATE'],
            // §3.8.4.4 / §3.8.5.1 / §3.8.5.2
            'RECURRENCE-ID as DATE' => ['RECURRENCE-ID', 'DATE'],
            'EXDATE as DATE' => ['EXDATE', 'DATE'],
            'RDATE as DATE' => ['RDATE', 'DATE'],
            'RDATE as PERIOD' => ['RDATE', 'PERIOD'],
            // §3.8.6.3
            'TRIGGER as DURATION' => ['TRIGGER', 'DURATION'],
            'TRIGGER as DATE-TIME' => ['TRIGGER', 'DATE-TIME'],
            // §3.8.1.1
            'ATTACH as URI' => ['ATTACH', 'URI'],
            'ATTACH as BINARY' => ['ATTACH', 'BINARY'],
            // RFC 7986 §5.10
            'IMAGE as URI' => ['IMAGE', 'URI'],
            'IMAGE as BINARY' => ['IMAGE', 'BINARY'],
            // RFC 9073 §6.5
            'STYLED-DESCRIPTION as TEXT' => ['STYLED-DESCRIPTION', 'TEXT'],
            'STYLED-DESCRIPTION as URI' => ['STYLED-DESCRIPTION', 'URI'],
            // redundant but legal: VALUE restating the default
            'SUMMARY as TEXT' => ['SUMMARY', 'TEXT'],
            'REFRESH-INTERVAL as DURATION' => ['REFRESH-INTERVAL', 'DURATION'],
        ];
    }

    #[DataProvider('legalValueTypeProvider')]
    public function testLegalValueTypeIsAccepted(string $property, string $type): void
    {
        $this->assertSame(
            $type,
            $this->factory->getParserForProperty($property, ['VALUE' => $type])->getType()
        );
    }

    /** Extensions must keep working: the library cannot know their value types. */
    public function testUnknownPropertyAcceptsAnyValueType(): void
    {
        foreach (['URI', 'TEXT', 'BINARY', 'INTEGER'] as $type) {
            $this->assertSame(
                $type,
                $this->factory->getParserForProperty('X-APPLE-STRUCTURED-LOCATION', ['VALUE' => $type])->getType()
            );
        }
    }

    /** VALUE is case-insensitive per §3.2. */
    public function testAllowlistIsCaseInsensitive(): void
    {
        $this->assertSame('DATE', $this->factory->getParserForProperty('DTSTART', ['VALUE' => 'date'])->getType());

        $this->expectException(ParseException::class);
        $this->factory->getParserForProperty('DTSTART', ['VALUE' => 'text']);
    }

    /** Absent VALUE still resolves to the property default. */
    public function testNoValueParameterUsesDefault(): void
    {
        $this->assertSame('DATE-TIME', $this->factory->getParserForProperty('DTSTART')->getType());
        $this->assertSame('TEXT', $this->factory->getParserForProperty('SUMMARY')->getType());
    }

    // -- end to end --

    private function calendarWith(string $line): string
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:test-uid\r\nDTSTAMP:20240101T000000Z\r\n"
            . "{$line}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    }

    /** The headline bypass: VALUE=TEXT must not disable validation on DTSTART. */
    public function testValueTextNoLongerBypassesValidationOnDtstart(): void
    {
        $this->expectException(ParseException::class);
        (new Parser(Parser::STRICT))->parse($this->calendarWith('DTSTART;VALUE=TEXT:total-garbage'));
    }

    public function testValueTextBypassWarnsInLenientMode(): void
    {
        $parser = new Parser(Parser::LENIENT);
        $parser->parse($this->calendarWith('DTSTART;VALUE=TEXT:total-garbage'));

        $this->assertNotEmpty($parser->getErrors(), 'lenient mode accepted DTSTART;VALUE=TEXT:total-garbage');
    }

    /** DTSTART;VALUE=DATE is the common real-world form and must still parse. */
    public function testLegalDateOverrideStillParses(): void
    {
        $calendar = (new Parser(Parser::STRICT))->parse($this->calendarWith('DTSTART;VALUE=DATE:20240115'));
        $dtstart = $calendar->getComponents()[0]->getProperty('DTSTART');

        $this->assertNotNull($dtstart);
        $this->assertSame('20240115', $dtstart->getValue()->getRawValue());
    }
}
