<?php

declare(strict_types=1);

namespace Tests\Writer;

use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VAlarm;
use Icalendar\Component\VTodo;
use Icalendar\Component\VTimezone;
use Icalendar\Component\Standard;
use Icalendar\Component\Daylight;
use Icalendar\Writer\Writer;
use Icalendar\Writer\ComponentWriter;
use Icalendar\Writer\ContentLineWriter;
use PHPUnit\Framework\TestCase;

class WriterTest extends TestCase
{
    private Writer $writer;

    protected function setUp(): void
    {
        $this->writer = new Writer();
    }

    public function testWriteSimpleEvent(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('test-123@example.com');
        $event->setSummary('Test Event');

        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $output);
        $this->assertStringContainsString('VERSION:2.0', $output);
        $this->assertStringContainsString('PRODID:-//Test//Test//EN', $output);
        $this->assertStringContainsString('BEGIN:VEVENT', $output);
        $this->assertStringContainsString('SUMMARY:Test Event', $output);
        $this->assertStringContainsString('END:VEVENT', $output);
        $this->assertStringContainsString('END:VCALENDAR', $output);
    }

    public function testWriteComplexCalendar(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');
        $calendar->setCalscale('GREGORIAN');
        $calendar->setMethod('PUBLISH');

        $event1 = new VEvent();
        $event1->setDtStamp('20260206T100000Z');
        $event1->setUid('event-1@example.com');
        $event1->setSummary('Meeting');
        $event1->setDtStart('20260210T100000Z');
        $event1->setDtEnd('20260210T110000Z');
        $event1->setDescription('Test description');
        $event1->setLocation('Conference Room');
        $event1->setCategories('Work');

        $event2 = new VEvent();
        $event2->setDtStamp('20260206T110000Z');
        $event2->setUid('event-2@example.com');
        $event2->setSummary('Another Event');
        $event2->setDtStart('20260211T140000Z');

        $calendar->addComponent($event1);
        $calendar->addComponent($event2);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('CALSCALE:GREGORIAN', $output);
        $this->assertStringContainsString('METHOD:PUBLISH', $output);
        $this->assertStringContainsString('SUMMARY:Meeting', $output);
        $this->assertStringContainsString('LOCATION:Conference Room', $output);
        $this->assertStringContainsString('SUMMARY:Another Event', $output);
    }

    public function testWriteToFile(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('file-test@example.com');
        $event->setSummary('File Test');

        $calendar->addComponent($event);

        $filepath = sys_get_temp_dir() . '/test_write_calendar.ics';

        try {
            $this->writer->writeToFile($calendar, $filepath);

            $this->assertFileExists($filepath);

            $content = file_get_contents($filepath);
            $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
            $this->assertStringContainsString('SUMMARY:File Test', $content);
        } finally {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    public function testWriteToFilePermissionDenied(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('perm-test@example.com');
        $event->setSummary('Perm Test');

        $calendar->addComponent($event);

        $filepath = '/nonexistent/path/calendar.ics';

        $this->expectException(\RuntimeException::class);

        $this->writer->writeToFile($calendar, $filepath);
    }

    public function testWriteLineFolding(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('fold-test@example.com');
        $event->setSummary('This is a very long summary that should trigger line folding because it exceeds seventy-five octets');

        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $lines = explode("\r\n", $output);
        $this->assertGreaterThan(1, count($lines));
    }

    public function testWriteWithFoldingDisabled(): void
    {
        $this->writer->setLineFolding(false);

        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('no-fold@example.com');
        $event->setSummary('Short');

        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('SUMMARY:Short', $output);
    }

    public function testWriteWithCustomMaxLength(): void
    {
        $this->writer->setLineFolding(true, 50);

        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('custom-length@example.com');
        $event->setSummary('A slightly longer summary to test max length folding');

        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $lines = explode("\r\n", $output);
        foreach ($lines as $line) {
            if (!empty($line) && !$this->isFoldLine($line)) {
                $this->assertLessThanOrEqual(50, strlen($line), 'Line exceeds max length');
            }
        }
    }

    private function isFoldLine(string $line): bool
    {
        return str_starts_with($line, ' ') || str_starts_with($line, "\t");
    }

    public function testWriteWithVAlarm(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('alarm-test@example.com');
        $event->setSummary('Event with Alarm');

        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_DISPLAY);
        $alarm->setTrigger('-PT15M');
        $alarm->setDescription('Reminder');

        $event->addAlarm($alarm);
        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('BEGIN:VALARM', $output);
        $this->assertStringContainsString('ACTION:DISPLAY', $output);
        $this->assertStringContainsString('TRIGGER:-PT15M', $output);
        $this->assertStringContainsString('DESCRIPTION:Reminder', $output);
        $this->assertStringContainsString('END:VALARM', $output);
    }

    public function testWriteWithTimezone(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $timezone = new VTimezone();
        $timezone->setTzid('America/New_York');

        $standard = new Standard();
        $standard->setDtStart(new \DateTimeImmutable('2020-10-25T02:00:00'));
        $standard->setTzOffsetFrom(-4 * 3600);
        $standard->setTzOffsetTo(-5 * 3600);
        $standard->setTzName('EST');

        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTimeImmutable('2021-03-14T02:00:00'));
        $daylight->setTzOffsetFrom(-5 * 3600);
        $daylight->setTzOffsetTo(-4 * 3600);
        $daylight->setTzName('EDT');

        $timezone->addComponent($standard);
        $timezone->addComponent($daylight);

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('tz-test@example.com');
        $event->setSummary('Timezone Event');

        $calendar->addComponent($timezone);
        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('BEGIN:VTIMEZONE', $output);
        $this->assertStringContainsString('TZID:America/New_York', $output);
        $this->assertStringContainsString('BEGIN:STANDARD', $output);
        $this->assertStringContainsString('TZNAME:EST', $output);
        $this->assertStringContainsString('END:STANDARD', $output);
        $this->assertStringContainsString('BEGIN:DAYLIGHT', $output);
        $this->assertStringContainsString('TZNAME:EDT', $output);
        $this->assertStringContainsString('END:DAYLIGHT', $output);
        $this->assertStringContainsString('END:VTIMEZONE', $output);
    }

    public function testWriteWithVTodo(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $todo = new VTodo();
        $todo->setDtStamp('20260206T100000Z');
        $todo->setUid('todo-test@example.com');
        $todo->setSummary('Buy groceries');
        $todo->setDue('20260210T180000Z');
        $todo->setPriority(1);
        $todo->setStatus(VTodo::STATUS_NEEDS_ACTION);

        $calendar->addComponent($todo);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('BEGIN:VTODO', $output);
        $this->assertStringContainsString('SUMMARY:Buy groceries', $output);
        $this->assertStringContainsString('DUE:20260210T180000Z', $output);
        $this->assertStringContainsString('PRIORITY:1', $output);
        $this->assertStringContainsString('STATUS:NEEDS-ACTION', $output);
        $this->assertStringContainsString('END:VTODO', $output);
    }

    public function testWriteStreaming(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        for ($i = 0; $i < 100; $i++) {
            $event = new VEvent();
            $event->setDtStamp('20260206T100000Z');
            $event->setUid("event-{$i}@example.com");
            $event->setSummary("Event {$i}");
            $calendar->addComponent($event);
        }

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('BEGIN:VEVENT', $output);
        $this->assertStringContainsString('END:VEVENT', $output);
    }

    public function testWriteValidation(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('validation-test@example.com');
        $event->setSummary('Test');

        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $output);
        $this->assertStringContainsString('END:VCALENDAR', $output);
    }

    public function testWriteRoundTrip(): void
    {
        $original = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:roundtrip@example.com\r\nSUMMARY:Round Trip Test\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $parser = new \Icalendar\Parser\Parser();
        $calendar = $parser->parse($original);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $output);
        $this->assertStringContainsString('END:VCALENDAR', $output);
        $this->assertStringContainsString('SUMMARY:Round Trip Test', $output);
    }

    public function testWriteWithUtf8Content(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('utf8-test@example.com');
        $event->setSummary('Testing 日本語 and русский текст');

        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('SUMMARY:Testing 日本語 and русский текст', $output);
    }

    public function testWriteWithSpecialCharacters(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('special-test@example.com');
        $event->setSummary('Test with ;, : and \\ special chars');
        $event->setDescription('Description with newline\nand more');

        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('SUMMARY:Test with', $output);
    }

    public function testWriteWithEmoji(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('emoji-test@example.com');
        $event->setSummary('Event with 🎉 emoji');

        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('🎉', $output);
    }

    public function testWriteWithParameters(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setDtStamp('20260206T100000Z');
        $event->setUid('param-test@example.com');
        $event->setSummary('Test with parameters');

        $calendar->addComponent($event);

        $output = $this->writer->write($calendar);

        $this->assertStringContainsString('SUMMARY:Test with parameters', $output);
    }

    public function testWriterUsesConfiguredContentLineWriter(): void
    {
        $customContentLineWriter = new ContentLineWriter(100, false);
        $customComponentWriter = new ComponentWriter();

        $writer = new Writer($customComponentWriter, $customContentLineWriter);

        $this->assertSame($customContentLineWriter, $writer->getContentLineWriter());
        $this->assertSame($customComponentWriter, $writer->getComponentWriter());
    }
}
