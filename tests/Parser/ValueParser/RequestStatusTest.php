<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\Parser;
use Icalendar\Parser\ValueParser\RequestStatusParser;
use Icalendar\Property\GenericProperty;
use Icalendar\Writer\PropertyWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * REQUEST-STATUS is a structured value (RFC 5545 §3.8.8.3):
 *
 *   statcode ";" statdesc [";" extdata]
 *
 * where statcode is dot-separated digits and the remaining parts are TEXT. The
 * semicolons are structural.
 *
 * It was absent from the property/type map, so it fell through to the TEXT
 * default: unvalidated on the way in, and on the way out the TEXT writer
 * escaped the separators, emitting `2.0\;Success`. A conformant reader cannot
 * split that back into a code and a description -- the same defect GEO had in
 * #18, and the reason a structured value must never be written as TEXT.
 */
class RequestStatusTest extends TestCase
{
    private RequestStatusParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new RequestStatusParser();
    }

    /** @return array<string, array{string}> */
    public static function validProvider(): array
    {
        return [
            'two parts' => ['2.0;Success'],
            'three parts' => ['3.7;Invalid calendar user;ATTENDEE:mailto:x@example.com'],
            'single digit code' => ['2;Success'],
            'deep code' => ['3.7.1;Detailed failure'],
            'escaped semicolon in description' => ['2.0;Success\\; with a literal semicolon'],
            'escaped comma in description' => ['2.0;Done\\, finally'],
        ];
    }

    #[DataProvider('validProvider')]
    public function testValidValuesParse(string $value): void
    {
        self::assertSame($value, $this->parser->parse($value));
        self::assertTrue($this->parser->canParse($value));
    }

    /** @return array<string, array{string}> */
    public static function invalidProvider(): array
    {
        return [
            'garbage' => ['garbage'],
            'empty' => [''],
            'no separator' => ['2.0'],
            'non-numeric code' => ['abc;Success'],
            'code with letters' => ['2.0a;Success'],
            'empty code' => [';Success'],
            'trailing dot in code' => ['2.;Success'],
            'four parts' => ['2.0;a;b;c'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidValuesAreRejected(string $value): void
    {
        self::assertFalse($this->parser->canParse($value));

        $this->expectException(ParseException::class);
        $this->parser->parse($value);
    }

    public function testUsesItsOwnErrorCode(): void
    {
        try {
            $this->parser->parse('garbage');
            self::fail('expected ParseException');
        } catch (ParseException $e) {
            self::assertSame(ParseException::ERR_INVALID_REQUEST_STATUS, $e->getErrorCode());
        }
    }

    /**
     * An escaped semicolon inside the description is content, not a separator,
     * so it must not push the value over the three-part limit.
     */
    public function testEscapedSemicolonDoesNotCountAsASeparator(): void
    {
        self::assertSame(
            '2.0;a\\;b\\;c',
            $this->parser->parse('2.0;a\\;b\\;c')
        );
    }

    public function testSeparatorsAreLiteralOnWrite(): void
    {
        $property = new GenericProperty(
            'REQUEST-STATUS',
            new \Icalendar\Value\GenericValue('2.0;Success', 'REQUEST-STATUS')
        );

        $line = (new PropertyWriter())->write($property);

        self::assertSame('REQUEST-STATUS:2.0;Success', $line);
        self::assertStringNotContainsString('\\;', $line);
    }

    public function testThreePartValueRoundTrips(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:rs@example.com\r\nDTSTAMP:20260101T000000Z\r\n"
            . "DTSTART:20260101T000000Z\r\n"
            . "REQUEST-STATUS:3.7;Invalid calendar user;ATTENDEE:mailto:x@example.com\r\n"
            . "END:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = (new Parser(Parser::LENIENT))->parse($ics);
        $events = $calendar->getComponents('VEVENT');
        self::assertNotEmpty($events);

        $property = $events[0]->getProperty('REQUEST-STATUS');
        self::assertNotNull($property);
        self::assertSame(
            '3.7;Invalid calendar user;ATTENDEE:mailto:x@example.com',
            $property->getValue()->getRawValue()
        );

        $written = (new PropertyWriter())->write($property);
        self::assertStringContainsString('3.7;Invalid calendar user;', $written);
        self::assertStringNotContainsString('3.7\\;', $written);
    }

    /**
     * Building the property by hand with GenericProperty::create() stores TEXT,
     * and the TEXT writer escapes the separators. addRequestStatus() stores a
     * REQUEST-STATUS-typed value so serialisation routes to the right writer --
     * the same requirement setGeo() has.
     */
    public function testAddRequestStatusKeepsSeparatorsLiteral(): void
    {
        $event = new \Icalendar\Component\VEvent();
        $event->addRequestStatus('2.0', 'Success');

        $property = $event->getProperty('REQUEST-STATUS');
        self::assertNotNull($property);
        self::assertSame('REQUEST-STATUS:2.0;Success', (new PropertyWriter())->write($property));
    }

    /**
     * A semicolon inside the description is content and must be escaped, while
     * the separators around it stay literal. Getting this backwards is the
     * whole defect.
     */
    public function testSemicolonInsideTheDescriptionIsEscaped(): void
    {
        $event = new \Icalendar\Component\VEvent();
        $event->addRequestStatus('3.7', 'Invalid; tricky', 'ATTENDEE:mailto:x@example.com');

        $property = $event->getProperty('REQUEST-STATUS');
        self::assertNotNull($property);

        self::assertSame(
            'REQUEST-STATUS:3.7;Invalid\\; tricky;ATTENDEE:mailto:x@example.com',
            (new PropertyWriter())->write($property)
        );
    }

    /** REQUEST-STATUS may occur more than once, so the setter accumulates. */
    public function testRequestStatusesAccumulate(): void
    {
        $event = new \Icalendar\Component\VEvent();
        $event->addRequestStatus('2.0', 'Success')
            ->addRequestStatus('2.8', 'Success, repeating event ignored');

        self::assertCount(2, $event->getRequestStatuses());
        self::assertSame('2.0;Success', $event->getRequestStatuses()[0]);
    }

    public function testInvalidValueIsRejectedInStrictMode(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:rs@example.com\r\nDTSTAMP:20260101T000000Z\r\n"
            . "DTSTART:20260101T000000Z\r\nREQUEST-STATUS:garbage\r\n"
            . "END:VEVENT\r\nEND:VCALENDAR\r\n";

        $this->expectException(ParseException::class);
        (new Parser(Parser::STRICT))->parse($ics);
    }
}
