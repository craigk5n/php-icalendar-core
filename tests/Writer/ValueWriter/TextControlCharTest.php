<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Parser\ValueParser\TextParser;
use Icalendar\Property\GenericProperty;
use Icalendar\Writer\ValueWriter\TextWriter;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Control characters must not reach a TEXT value.
 *
 * RFC 5545 §3.3.11's TEXT ABNF excludes CONTROL (%x00-08, %x0A-1F, %x7F),
 * admitting only HTAB. TextWriter escaped backslash, semicolon, comma and CRLF
 * but passed control bytes through untouched, so any caller handing it
 * arbitrary input (a commit message, a scraped field) emitted illegal output.
 *
 * SecurityValidator::sanitizeText() already did exactly the right thing and was
 * never called from src/ -- it is now wired into TextWriter::write(), so callers
 * benefit without knowing the class exists.
 *
 * Ordering matters and is asserted below: sanitize first, then escape.
 * sanitizeText() emits a backslash ('\x01'), and escape() must be the thing that
 * doubles it. Reversed, the output carries a bare '\x' -- not a defined escape
 * in §3.3.11 -- and reparses lossily to 'x01'.
 *
 * The ABNF's CONTROL set includes DEL (%x7F), not just the C0 range, so
 * sanitizeText() escapes it too; testDelIsEscaped covers that.
 */
class TextControlCharTest extends TestCase
{
    private TextWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->writer = new TextWriter();
    }

    /** @return array<string, array{string}> */
    public static function controlCharProvider(): array
    {
        return [
            'SOH 0x01' => ["\x01"],
            'STX 0x02' => ["\x02"],
            'BEL 0x07' => ["\x07"],
            'VT 0x0B' => ["\x0B"],
            'FF 0x0C' => ["\x0C"],
            'ESC 0x1B' => ["\x1B"],
            'US 0x1F' => ["\x1F"],
            'DEL 0x7F' => ["\x7F"],
        ];
    }

    #[DataProvider('controlCharProvider')]
    public function testControlCharsAreNotEmittedRaw(string $char): void
    {
        $output = $this->writer->write("before{$char}after");

        $this->assertStringNotContainsString(
            $char,
            $output,
            'raw control byte reached the TEXT value'
        );
    }

    public function testNullByteIsRemoved(): void
    {
        $this->assertStringNotContainsString("\x00", $this->writer->write("a\x00b"));
    }

    /**
     * DEL (0x7F) is in the ABNF's CONTROL set and must not reach the output.
     * A DEL-only value (no C0 byte) must still trigger the scan, i.e. the
     * fast-path filter has to include 0x7F.
     */
    public function testDelIsEscaped(): void
    {
        $output = $this->writer->write("a\x7Fb");

        $this->assertStringNotContainsString("\x7F", $output);
        $this->assertSame('a\\\\x7fb', $output);
    }

    /** HTAB is explicitly permitted by the TEXT ABNF and must survive. */
    public function testTabIsPreserved(): void
    {
        $this->assertStringContainsString("\t", $this->writer->write("a\tb"));
    }

    /** Newlines keep their existing \n escaping, not \x0a. */
    public function testNewlinesStillEscapeAsBackslashN(): void
    {
        $this->assertSame('a\\nb', $this->writer->write("a\nb"));
        $this->assertSame('a\\nb', $this->writer->write("a\r\nb"));
    }

    /** Ordinary text must be untouched by sanitisation. */
    public function testPlainTextIsUnchanged(): void
    {
        $this->assertSame('Hello world', $this->writer->write('Hello world'));
    }

    /** Existing RFC escaping must still apply. */
    public function testEscapingStillApplies(): void
    {
        $this->assertSame('a\\;b\\,c\\\\d', $this->writer->write('a;b,c\\d'));
    }

    /** Unicode must not be mangled by the byte-wise scan. */
    public function testUnicodeSurvives(): void
    {
        foreach (['日本語', 'Ünïcödé', '🎉 emoji', 'Привет'] as $text) {
            $this->assertSame($text, $this->writer->write($text));
        }
    }

    /**
     * Sanitise-then-escape: the emitted backslash must itself be escaped, so the
     * value reparses to a stable literal rather than a bare '\x' escape.
     */
    public function testControlCharSurvivesAsEscapedLiteral(): void
    {
        $output = $this->writer->write("before\x01after");

        $this->assertSame('before\\\\x01after', $output);
        $this->assertSame('before\\x01after', (new TextParser())->parse($output));
    }

    /** End to end: a control byte must not reach the serialised calendar. */
    public function testControlCharDoesNotReachCalendarOutput(): void
    {
        $calendar = new VCalendar();
        $calendar->addProperty(GenericProperty::create('PRODID', '-//test//test//EN'));
        $calendar->addProperty(GenericProperty::create('VERSION', '2.0'));
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('UID', 'test-uid'));
        $event->addProperty(GenericProperty::create('DTSTAMP', '20260206T100000Z'));
        $event->addProperty(GenericProperty::create('DESCRIPTION', "before\x01\x02after"));
        $calendar->addComponent($event);

        $output = (new Writer())->write($calendar);

        $this->assertStringNotContainsString("\x01", $output);
        $this->assertStringNotContainsString("\x02", $output);
    }
}
