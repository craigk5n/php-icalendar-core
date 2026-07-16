<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\DateParser;
use Icalendar\Parser\ValueParser\DateTimeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Lenient mode must never guess at a date value.
 *
 * Previously both date parsers fell back to PHP's DateTimeImmutable constructor
 * when a value failed the RFC 5545 format check. That accepted relative
 * expressions ("now", "tomorrow"), which resolve against the wall clock and make
 * parsing non-deterministic, and it accepted the empty string as "now". Lenient
 * mode collects a warning; it does not invent data.
 */
class LenientDateFallbackTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function nonRfcValueProvider(): array
    {
        return [
            'relative now' => ['now'],
            'relative tomorrow' => ['tomorrow'],
            'relative yesterday' => ['yesterday'],
            'relative next friday' => ['next friday'],
            'relative offset' => ['+1 day'],
            'relative ago' => ['5 weeks ago'],
            'midnight keyword' => ['midnight'],
            'empty string' => [''],
            'whitespace only' => ['   '],
            'leading space' => [' something'],
            'iso 8601 dashes' => ['2024-01-01'],
            'unix epoch at-syntax' => ['@0'],
            'all zeroes' => ['00000000'],
            'nine digits' => ['999999999'],
            'arbitrary text' => ['garbage'],
        ];
    }

    #[DataProvider('nonRfcValueProvider')]
    public function testDateTimeParserRejectsNonRfcValuesInLenientMode(string $value): void
    {
        $parser = new DateTimeParser();
        $parser->setStrict(false);

        $this->expectException(ParseException::class);
        $parser->parse($value);
    }

    #[DataProvider('nonRfcValueProvider')]
    public function testDateParserRejectsNonRfcValuesInLenientMode(string $value): void
    {
        $parser = new DateParser();
        $parser->setStrict(false);

        $this->expectException(ParseException::class);
        $parser->parse($value);
    }

    #[DataProvider('nonRfcValueProvider')]
    public function testCanParseIsFalseForNonRfcValuesInLenientMode(string $value): void
    {
        $parser = new DateTimeParser();
        $parser->setStrict(false);

        $this->assertFalse($parser->canParse($value));
    }

    /**
     * Lenient and strict must agree on *what is a date*. They may only differ in
     * how the failure is reported, never in which values are accepted.
     */
    #[DataProvider('nonRfcValueProvider')]
    public function testStrictAndLenientAgreeOnRejection(string $value): void
    {
        $strict = new DateTimeParser();
        $strict->setStrict(true);
        $lenient = new DateTimeParser();
        $lenient->setStrict(false);

        $strictRejected = false;
        try {
            $strict->parse($value);
        } catch (ParseException) {
            $strictRejected = true;
        }

        $lenientRejected = false;
        try {
            $lenient->parse($value);
        } catch (ParseException) {
            $lenientRejected = true;
        }

        $this->assertTrue($strictRejected, 'strict mode should reject');
        $this->assertSame(
            $strictRejected,
            $lenientRejected,
            "strict and lenient disagree on '{$value}'"
        );
    }

    /** Valid RFC values must still parse in lenient mode. */
    public function testLenientModeStillAcceptsValidRfcDateTime(): void
    {
        $parser = new DateTimeParser();
        $parser->setStrict(false);

        $dt = $parser->parse('20260206T100000Z');
        $this->assertSame('2026-02-06 10:00:00', $dt->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $dt->getTimezone()->getName());
    }

    public function testLenientModeStillAcceptsValidRfcDate(): void
    {
        $parser = new DateParser();
        $parser->setStrict(false);

        $this->assertSame('20260206', $parser->parse('20260206')->format('Ymd'));
    }

    /**
     * Regression: parsing must not depend on the wall clock. "now" previously
     * produced a different value on every call.
     */
    public function testParsingIsDeterministic(): void
    {
        $parser = new DateTimeParser();
        $parser->setStrict(false);

        foreach (['now', ''] as $value) {
            try {
                $parser->parse($value);
                $this->fail("'{$value}' must not resolve against the wall clock");
            } catch (ParseException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
