<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer;

use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;
use Icalendar\Writer\Writer;
use Icalendar\Writer\WriterInterface;
use PHPUnit\Framework\TestCase;

/**
 * writeValidated() is the opt-in "validate, then write" entry point.
 *
 * write() serialises whatever it is handed and never validates -- a deliberate
 * design choice (write is not validate), but one that lets non-conformant output
 * ship with no signal. writeValidated() closes that for callers who want it,
 * without changing write()'s contract.
 *
 * It uses the component tree's own validate() (recursive as of the recursion
 * change), which is fail-fast: the first violation throws. For collecting every
 * error instead, callers run Validator::validate() themselves before writing.
 */
class WriteValidatedTest extends TestCase
{
    private function validCalendar(): VCalendar
    {
        $calendar = new VCalendar();
        $calendar->addProperty(GenericProperty::create('PRODID', '-//test//test//EN'));
        $calendar->addProperty(GenericProperty::create('VERSION', '2.0'));

        return $calendar;
    }

    private function validEvent(): VEvent
    {
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('UID', 'test-uid'));
        $event->addProperty(GenericProperty::create('DTSTAMP', '20240101T000000Z'));

        return $event;
    }

    public function testValidCalendarWritesSameAsWrite(): void
    {
        $calendar = $this->validCalendar();
        $calendar->addComponent($this->validEvent());

        $writer = new Writer();

        $this->assertSame($writer->write($calendar), $writer->writeValidated($calendar));
    }

    public function testMissingProdidThrows(): void
    {
        $calendar = new VCalendar();
        $calendar->addProperty(GenericProperty::create('VERSION', '2.0'));

        $this->expectException(ValidationException::class);
        (new Writer())->writeValidated($calendar);
    }

    /** Recursion: an invalid child must fail the validated write. */
    public function testInvalidChildThrows(): void
    {
        $calendar = $this->validCalendar();
        $calendar->addComponent(new VEvent()); // no UID, no DTSTAMP

        $this->expectException(ValidationException::class);
        (new Writer())->writeValidated($calendar);
    }

    /** Nothing is written when validation fails. */
    public function testNoOutputEscapesOnFailure(): void
    {
        $calendar = $this->validCalendar();
        $calendar->addComponent(new VEvent());

        try {
            (new Writer())->writeValidated($calendar);
            $this->fail('expected validation to fail');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('VEVENT', $e->getMessage());
        }
    }

    /**
     * The whole point of option B: write() itself stays unvalidating, so
     * existing callers that serialise partial calendars are unaffected.
     */
    public function testWriteStillDoesNotValidate(): void
    {
        $calendar = new VCalendar(); // no PRODID, no VERSION
        $calendar->addComponent(new VEvent());

        $output = (new Writer())->write($calendar);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $output);
        $this->assertStringContainsString('BEGIN:VEVENT', $output);
    }

    /** Reachable through the interface type, not only the concrete class. */
    public function testReachableThroughInterface(): void
    {
        $writer = new Writer();
        $this->assertInstanceOf(WriterInterface::class, $writer);

        $calendar = $this->validCalendar();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $this->writeVia($writer, $calendar));
    }

    /** writeValidated() is a gate, not a transform: output must equal write(). */
    public function testOutputIsByteIdenticalToWriteForValidInput(): void
    {
        $calendar = $this->validCalendar();
        $calendar->addComponent($this->validEvent());

        $writer = new Writer();
        $validated = $writer->writeValidated($calendar);

        $this->assertSame($writer->write($calendar), $validated);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $validated);
    }

    /** Folding configuration still applies -- writeValidated delegates to write(). */
    public function testLineFoldingConfigStillApplies(): void
    {
        $calendar = $this->validCalendar();
        $event = $this->validEvent();
        $event->addProperty(GenericProperty::create('SUMMARY', str_repeat('A', 200)));
        $calendar->addComponent($event);

        $writer = new Writer();
        $writer->setLineFolding(false);

        // With folding off, the 200-char SUMMARY stays on one line.
        $this->assertStringContainsString('SUMMARY:' . str_repeat('A', 200), $writer->writeValidated($calendar));
    }

    private function writeVia(WriterInterface $writer, VCalendar $calendar): string
    {
        return $writer->writeValidated($calendar);
    }
}
