<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer;

use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Parser\Parser;
use Icalendar\Property\GenericProperty;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\TestCase;

/**
 * The serialised calendar must end with CRLF.
 *
 * RFC 5545 §3.4 ABNF:
 *
 *   icalobject = "BEGIN" ":" "VCALENDAR" CRLF icalbody "END" ":" "VCALENDAR" CRLF
 *
 * The final CRLF is part of the grammar, so strict parsers are entitled to
 * reject output without it. ContentLineWriter::write() strips the trailing CRLF
 * so that exploding on it does not yield an empty final element; that is correct
 * for a folding utility working on a fragment, so the terminator is restored
 * here, at the document level.
 */
class TrailingCrlfTest extends TestCase
{
    private function minimalCalendar(): VCalendar
    {
        $calendar = new VCalendar();
        $calendar->addProperty(GenericProperty::create('PRODID', '-//test//test//EN'));
        $calendar->addProperty(GenericProperty::create('VERSION', '2.0'));

        return $calendar;
    }

    public function testMinimalCalendarEndsWithCrlf(): void
    {
        $output = (new Writer())->write($this->minimalCalendar());

        $this->assertStringEndsWith("END:VCALENDAR\r\n", $output);
    }

    public function testCalendarWithEventEndsWithCrlf(): void
    {
        $calendar = $this->minimalCalendar();
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('UID', 'test-uid'));
        $event->addProperty(GenericProperty::create('DTSTAMP', '20260206T100000Z'));
        $calendar->addComponent($event);

        $this->assertStringEndsWith("END:VCALENDAR\r\n", (new Writer())->write($calendar));
    }

    /** Exactly one terminator: no blank line at the end. */
    public function testDoesNotEndWithDoubleCrlf(): void
    {
        $output = (new Writer())->write($this->minimalCalendar());

        $this->assertStringEndsWith("\r\n", $output);
        $this->assertStringNotContainsString("\r\n\r\n", $output);
    }

    public function testFoldedOutputEndsWithCrlf(): void
    {
        $calendar = $this->minimalCalendar();
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('UID', 'test-uid'));
        $event->addProperty(GenericProperty::create('DTSTAMP', '20260206T100000Z'));
        // Long enough to force folding.
        $event->addProperty(GenericProperty::create('SUMMARY', str_repeat('A', 200)));
        $calendar->addComponent($event);

        $this->assertStringEndsWith("END:VCALENDAR\r\n", (new Writer())->write($calendar));
    }

    public function testUnfoldedOutputEndsWithCrlf(): void
    {
        $writer = new Writer();
        $writer->setLineFolding(false);

        $this->assertStringEndsWith("END:VCALENDAR\r\n", $writer->write($this->minimalCalendar()));
    }

    public function testWriteToFileEndsWithCrlf(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ical-crlf-');
        $this->assertNotFalse($path);

        try {
            (new Writer())->writeToFile($this->minimalCalendar(), $path);
            $this->assertStringEndsWith("END:VCALENDAR\r\n", (string) file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    /** The terminator must not break the parse -> write -> parse cycle. */
    public function testTerminatedOutputReparses(): void
    {
        $output = (new Writer())->write($this->minimalCalendar());

        $reparsed = (new Parser(Parser::STRICT))->parse($output);
        $this->assertSame('VCALENDAR', $reparsed->getName());

        // And stays stable on a second pass.
        $this->assertSame($output, (new Writer())->write($reparsed));
    }
}
