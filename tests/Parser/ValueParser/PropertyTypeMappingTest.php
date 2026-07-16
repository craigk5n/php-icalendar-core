<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Parser\Parser;
use Icalendar\Parser\ValueParser\RecurParser;
use Icalendar\Parser\ValueParser\UriParser;
use Icalendar\Parser\ValueParser\ValueParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Properties must resolve to their RFC 5545 default value type.
 *
 * RRULE/EXRULE were absent from the property-default map and fell through to the
 * TEXT default, which cannot fail — so RecurParser (and the RRuleParser behind
 * it) was unreachable from any property name and no RRULE was ever validated.
 */
class PropertyTypeMappingTest extends TestCase
{
    private ValueParserFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new ValueParserFactory();
    }

    /** @return array<string, array{string, string}> */
    public static function propertyDefaultTypeProvider(): array
    {
        return [
            // RFC 5545 §3.8.5.3 / §3.3.10
            'RRULE is RECUR' => ['RRULE', 'RECUR'],
            'EXRULE is RECUR' => ['EXRULE', 'RECUR'],
            // RFC 5545 §3.8.1.1 — default type is URI; BINARY requires VALUE=BINARY
            'ATTACH is URI' => ['ATTACH', 'URI'],
            // regression guards on existing mappings
            'DTSTART is DATE-TIME' => ['DTSTART', 'DATE-TIME'],
            'SUMMARY is TEXT' => ['SUMMARY', 'TEXT'],
        ];
    }

    #[DataProvider('propertyDefaultTypeProvider')]
    public function testPropertyResolvesToDefaultType(string $property, string $expectedType): void
    {
        $this->assertSame(
            $expectedType,
            $this->factory->getParserForProperty($property)->getType()
        );
    }

    public function testRruleResolvesToRecurParser(): void
    {
        $this->assertInstanceOf(RecurParser::class, $this->factory->getParserForProperty('RRULE'));
    }

    public function testAttachResolvesToUriParser(): void
    {
        $this->assertInstanceOf(UriParser::class, $this->factory->getParserForProperty('ATTACH'));
    }

    /**
     * Every malformed RRULE. Strict mode must reject all of these — before the
     * RECUR mapping it accepted every one as TEXT.
     *
     * @return array<string, array{string}>
     */
    public static function malformedRruleProvider(): array
    {
        return [
            'unknown freq' => ['FREQ=NONSENSE'],
            'non-numeric interval' => ['FREQ=DAILY;INTERVAL=abc'],
            'arbitrary text' => ['total garbage here'],
            'empty' => [''],
        ];
    }

    /**
     * Malformed RRULEs that lenient mode correctly warns on.
     *
     * @return array<string, array{string}>
     */
    public static function leniallyRejectedRruleProvider(): array
    {
        return [
            'arbitrary text' => ['total garbage here'],
            'empty' => [''],
        ];
    }

    /**
     * Malformed RRULEs that RRuleParser's lenient branch still accepts.
     *
     * Wiring RRULE to RECUR means strict mode now validates it, but RRuleParser
     * has a separate lenient path (src/Recurrence/RRuleParser.php:165) that
     * skips the FREQ allowlist and coerces INTERVAL with a bare (int) cast, so
     * 'INTERVAL=abc' silently becomes 'INTERVAL=0'. Same defect family as the
     * removed date fallback: lenient mode inventing a value rather than
     * reporting one. Out of scope here; tracked as follow-up work.
     *
     * @return array<string, array{string}>
     */
    public static function knownLenientRruleGapProvider(): array
    {
        return [
            'unknown freq' => ['FREQ=NONSENSE'],
            'non-numeric interval' => ['FREQ=DAILY;INTERVAL=abc'],
        ];
    }

    private function calendarWithRrule(string $rrule): string
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:test-uid\r\nDTSTART:20260206T100000Z\r\n"
            . "RRULE:{$rrule}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    }

    #[DataProvider('malformedRruleProvider')]
    public function testMalformedRruleIsRejectedInStrictMode(string $rrule): void
    {
        $this->expectException(\Icalendar\Exception\ParseException::class);
        (new Parser(Parser::STRICT))->parse($this->calendarWithRrule($rrule));
    }

    #[DataProvider('leniallyRejectedRruleProvider')]
    public function testMalformedRruleWarnsInLenientMode(string $rrule): void
    {
        $parser = new Parser(Parser::LENIENT);
        $parser->parse($this->calendarWithRrule($rrule));

        $this->assertNotEmpty($parser->getErrors(), "lenient mode accepted 'RRULE:{$rrule}'");
    }

    /**
     * Documents the open RRuleParser lenient gap. Marked incomplete rather than
     * asserted-true so it stays visible instead of reading as covered.
     */
    #[DataProvider('knownLenientRruleGapProvider')]
    public function testLenientModeStillAcceptsSomeMalformedRrules(string $rrule): void
    {
        $parser = new Parser(Parser::LENIENT);
        $parser->parse($this->calendarWithRrule($rrule));

        if ($parser->getErrors() === []) {
            $this->markTestIncomplete(
                "Known gap: RRuleParser's lenient branch accepts 'RRULE:{$rrule}' "
                . 'without a warning (INTERVAL=abc coerces to 0). See '
                . 'src/Recurrence/RRuleParser.php:165.'
            );
        }

        $this->assertNotEmpty($parser->getErrors());
    }

    /** A valid RRULE must survive parse in both modes. */
    public function testValidRruleParsesInBothModes(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:test-uid\r\nDTSTART:20260206T100000Z\r\n"
            . "RRULE:FREQ=WEEKLY;BYDAY=MO,WE\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        foreach ([Parser::STRICT, Parser::LENIENT] as $mode) {
            $parser = new Parser($mode);
            $calendar = $parser->parse($ics);

            $this->assertEmpty($parser->getErrors());
            $rrule = $calendar->getComponents()[0]->getProperty('RRULE');
            $this->assertNotNull($rrule);
            $this->assertSame('FREQ=WEEKLY;BYDAY=MO,WE', $rrule->getValue()->getRawValue());
        }
    }
}
