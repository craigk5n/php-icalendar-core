<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\Parser;
use Icalendar\Validation\ErrorSeverity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Same malformed input, both modes, asserted outcomes.
 *
 * The suite previously exercised STRICT and LENIENT only on valid data, so
 * lenient mode silently accepting non-RFC dates went unnoticed.
 */
class MalformedValueModeTest extends TestCase
{
    private function calendarWith(string $line): string
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:test-uid\r\n{$line}\r\nEND:VEVENT\r\n"
            . "END:VCALENDAR\r\n";
    }

    /** @return array<string, array{string}> */
    public static function malformedDateLineProvider(): array
    {
        return [
            'relative now' => ['DTSTART:now'],
            'relative tomorrow' => ['DTSTART:tomorrow'],
            'empty value' => ['DTSTART:'],
            'arbitrary text' => ['DTSTART:garbage'],
            'leading space' => ['DTSTART: something'],
            'iso dashes' => ['DTSTART:2024-01-01'],
            'all zeroes' => ['DTSTART:00000000'],
            'bad month' => ['DTSTART:20241345T100000Z'],
            'feb 30' => ['DTSTART:20240230T100000Z'],
            'bad hour' => ['DTSTART:20260206T990000Z'],
            'dtend relative' => ['DTEND:tomorrow'],
            'dtstamp relative' => ['DTSTAMP:now'],
        ];
    }

    #[DataProvider('malformedDateLineProvider')]
    public function testStrictModeThrows(string $line): void
    {
        $this->expectException(ParseException::class);
        (new Parser(Parser::STRICT))->parse($this->calendarWith($line));
    }

    /**
     * Lenient mode must record a WARNING-severity error rather than accepting
     * the value. Nothing previously asserted the severity.
     */
    #[DataProvider('malformedDateLineProvider')]
    public function testLenientModeRecordsWarningAndDoesNotInventValue(string $line): void
    {
        $parser = new Parser(Parser::LENIENT);
        $calendar = $parser->parse($this->calendarWith($line));

        $errors = $parser->getErrors();
        $this->assertNotEmpty($errors, "lenient mode silently accepted '{$line}'");
        $this->assertSame(ErrorSeverity::WARNING, $errors[0]->severity);

        $propertyName = strtok($line, ':;');
        $event = $calendar->getComponents()[0];
        $this->assertNull(
            $event->getProperty((string) $propertyName),
            "lenient mode fabricated a value for '{$line}'"
        );
    }

    /** Valid input must stay clean in both modes. */
    public function testValidDateProducesNoErrorsInEitherMode(): void
    {
        foreach ([Parser::STRICT, Parser::LENIENT] as $mode) {
            $parser = new Parser($mode);
            $calendar = $parser->parse($this->calendarWith('DTSTART:20260206T100000Z'));

            $this->assertEmpty($parser->getErrors());
            $event = $calendar->getComponents()[0];
            $this->assertNotNull($event->getProperty('DTSTART'));
        }
    }
}
