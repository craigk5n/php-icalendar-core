<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Parser\Parser;
use Icalendar\Writer\ValueWriter\BooleanWriter;
use Icalendar\Writer\ValueWriter\FloatWriter;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Anything the parser produces must survive being written back.
 *
 * The writer boundary is stringly typed by contract: ValueInterface declares
 * getRawValue(): string, every implementation honours it -- DateTimeValue's
 * returns serialize(), not its DateTimeInterface -- and PropertyWriter passes
 * exactly that into ValueWriterFactory::write(). So a writer can never receive a
 * native PHP type through the library's own write path.
 *
 * Most writers accept the string accordingly: IntegerWriter opens with
 * `if (is_string($value)) { return $value; }`, and DateTime, Recur, Uri,
 * CalAddress and Binary do likewise. BOOLEAN and FLOAT did not, so they threw on
 * every value the parser could hand them:
 *
 *   RSVP:TRUE                 -> parse OK, then InvalidArgumentException on write
 *   X-CUSTOM;VALUE=FLOAT:1.5  -> parse OK, then InvalidArgumentException on write
 *
 * That is untrusted input reaching an unhandled exception -- not a ParseException
 * or ValidationException but a raw InvalidArgumentException, escaping the
 * library's own hierarchy -- in STRICT mode, on a plain parse->write. RSVP needs
 * no VALUE abuse: it is a mapped BOOLEAN property, so the VALUE allowlist cannot
 * close this. Extensions declaring VALUE=FLOAT are the other way in.
 *
 * Accepting the canonical serialisation is not accepting anything: the loose
 * values BooleanWriterTest guards against (1, 0, '1', 'yes', 'true', [], null)
 * are still rejected, with the same message.
 */
class ParsedValueRoundTripTest extends TestCase
{
    private function calendarWith(string $line): string
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:test-uid\r\nDTSTAMP:20240101T000000Z\r\n"
            . "{$line}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    }

    /** @return array<string, array{string}> */
    public static function parsedPropertyProvider(): array
    {
        return [
            'boolean true' => ['RSVP:TRUE'],
            'boolean false' => ['RSVP:FALSE'],
            'extension boolean' => ['X-CUSTOM;VALUE=BOOLEAN:TRUE'],
            'extension float' => ['X-CUSTOM;VALUE=FLOAT:1.5'],
            'extension negative float' => ['X-CUSTOM;VALUE=FLOAT:-3.25'],
            'extension integer' => ['X-CUSTOM;VALUE=INTEGER:42'],
            'extension uri' => ['X-CUSTOM;VALUE=URI:https://example.com/x'],
            'extension date-time' => ['X-CUSTOM;VALUE=DATE-TIME:20240101T120000Z'],
        ];
    }

    /** Untrusted input must never reach an unhandled exception on write. */
    #[DataProvider('parsedPropertyProvider')]
    public function testParsedPropertySurvivesWrite(string $line): void
    {
        $calendar = (new Parser(Parser::STRICT))->parse($this->calendarWith($line));

        $this->assertStringContainsString('BEGIN:VEVENT', (new Writer())->write($calendar));
    }

    public function testBooleanRoundTripsThroughWrite(): void
    {
        foreach (['TRUE', 'FALSE'] as $expected) {
            $calendar = (new Parser(Parser::STRICT))->parse($this->calendarWith("RSVP:{$expected}"));

            $this->assertStringContainsString("RSVP:{$expected}", (new Writer())->write($calendar));
        }
    }

    public function testFloatRoundTripsThroughWrite(): void
    {
        $calendar = (new Parser(Parser::STRICT))->parse($this->calendarWith('X-CUSTOM;VALUE=FLOAT:1.5'));

        $this->assertStringContainsString('1.5', (new Writer())->write($calendar));
    }

    // -- writer units --

    public function testBooleanWriterAcceptsNativeBool(): void
    {
        $writer = new BooleanWriter();
        $this->assertSame('TRUE', $writer->write(true));
        $this->assertSame('FALSE', $writer->write(false));
    }

    /** The canonical serialisation the parser hands back. */
    public function testBooleanWriterAcceptsCanonicalStrings(): void
    {
        $writer = new BooleanWriter();
        $this->assertSame('TRUE', $writer->write('TRUE'));
        $this->assertSame('FALSE', $writer->write('FALSE'));
    }

    /**
     * The loose-typing guard stays intact: only the exact canonical spelling is
     * accepted, so PHP's coercions cannot sneak a truthy value through.
     *
     * @return array<string, array{mixed}>
     */
    public static function looseValueProvider(): array
    {
        return [
            'lowercase true' => ['true'],
            'lowercase false' => ['false'],
            'mixed case' => ['True'],
            'string one' => ['1'],
            'string zero' => ['0'],
            'yes' => ['yes'],
            'int one' => [1],
            'int zero' => [0],
            'float' => [1.0],
            'array' => [[]],
            'null' => [null],
        ];
    }

    #[DataProvider('looseValueProvider')]
    public function testBooleanWriterStillRejectsLooseValues(mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new BooleanWriter())->write($value);
    }

    public function testFloatWriterAcceptsNumericStrings(): void
    {
        $writer = new FloatWriter();
        $this->assertSame('1.5', $writer->write('1.5'));
        $this->assertSame('-3.25', $writer->write('-3.25'));
    }

    public function testFloatWriterRejectsNonNumericStrings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new FloatWriter())->write('garbage');
    }

    public function testFloatWriterStillAcceptsNativeFloat(): void
    {
        $this->assertSame('1.5', (new FloatWriter())->write(1.5));
    }

    /**
     * canWrite() must agree with write(). Widening one without the other leaves
     * the pair silently contradicting each other.
     *
     * @return array<string, array{mixed}>
     */
    public static function writerInputProvider(): array
    {
        return [
            'bool true' => [true],
            'bool false' => [false],
            'string TRUE' => ['TRUE'],
            'string FALSE' => ['FALSE'],
            'lowercase true' => ['true'],
            'numeric string' => ['1.5'],
            'negative numeric string' => ['-3.25'],
            'int' => [42],
            'float' => [1.5],
            'garbage string' => ['garbage'],
            'empty string' => [''],
            'array' => [[]],
            'null' => [null],
        ];
    }

    #[DataProvider('writerInputProvider')]
    public function testBooleanWriterCanWriteAgreesWithWrite(mixed $value): void
    {
        $writer = new BooleanWriter();
        $accepted = true;

        try {
            $writer->write($value);
        } catch (\InvalidArgumentException) {
            $accepted = false;
        }

        $this->assertSame($accepted, $writer->canWrite($value), 'canWrite() disagrees with write()');
    }

    #[DataProvider('writerInputProvider')]
    public function testFloatWriterCanWriteAgreesWithWrite(mixed $value): void
    {
        $writer = new FloatWriter();
        $accepted = true;

        try {
            $writer->write($value);
        } catch (\InvalidArgumentException) {
            $accepted = false;
        }

        $this->assertSame($accepted, $writer->canWrite($value), 'canWrite() disagrees with write()');
    }
}
