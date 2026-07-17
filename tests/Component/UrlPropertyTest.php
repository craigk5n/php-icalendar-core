<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VEvent;
use Icalendar\Component\VFreeBusy;
use Icalendar\Component\VJournal;
use Icalendar\Component\VTodo;
use Icalendar\Parser\Parser;
use Icalendar\Property\GenericProperty;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * URL must be reachable on every component RFC 5545 permits it on.
 *
 * §3.8.4.6: "This property can be specified in VEVENT, VTODO, VJOURNAL, or
 * VFREEBUSY calendar components." VEvent and VTodo each carried their own
 * byte-identical setUrl()/getUrl(); VJournal and VFreeBusy had neither, so
 * callers had to drop to GenericProperty::create('URL', ...) for two of the four
 * while the other two offered a setter. The asymmetry is the trap: nothing
 * signals which components share a property surface.
 *
 * The duplicated pair is now a shared trait rather than a third and fourth copy.
 */
class UrlPropertyTest extends TestCase
{
    /**
     * Factories rather than class strings: `new $class()` cannot be verified by
     * static analysis, and each call here also yields a fresh component, so a
     * mutating test cannot leak state into another data set.
     *
     * @return array<string, array{callable(): (VEvent|VTodo|VJournal|VFreeBusy)}>
     */
    public static function urlCapableComponentProvider(): array
    {
        return [
            'VEVENT' => [static fn (): VEvent => new VEvent()],
            'VTODO' => [static fn (): VTodo => new VTodo()],
            'VJOURNAL' => [static fn (): VJournal => new VJournal()],
            'VFREEBUSY' => [static fn (): VFreeBusy => new VFreeBusy()],
        ];
    }

    /** @param callable(): (VEvent|VTodo|VJournal|VFreeBusy) $make */
    #[DataProvider('urlCapableComponentProvider')]
    public function testSetUrlAndGetUrl(callable $make): void
    {
        $component = $make();
        $component->setUrl('https://example.com/thing');

        $this->assertSame('https://example.com/thing', $component->getUrl());
    }

    /** @param callable(): (VEvent|VTodo|VJournal|VFreeBusy) $make */
    #[DataProvider('urlCapableComponentProvider')]
    public function testGetUrlIsNullWhenUnset(callable $make): void
    {
        $this->assertNull($make()->getUrl());
    }

    /** Matches the fluent style of the other setters. */
    /** @param callable(): (VEvent|VTodo|VJournal|VFreeBusy) $make */
    #[DataProvider('urlCapableComponentProvider')]
    public function testSetUrlIsFluent(callable $make): void
    {
        $component = $make();

        $this->assertSame($component, $component->setUrl('https://example.com/'));
    }

    /** URL is single-occurrence (§3.8.4.6): setting twice must replace, not append. */
    /** @param callable(): (VEvent|VTodo|VJournal|VFreeBusy) $make */
    #[DataProvider('urlCapableComponentProvider')]
    public function testSetUrlReplacesRatherThanAppends(callable $make): void
    {
        $component = $make();
        $component->setUrl('https://example.com/first');
        $component->setUrl('https://example.com/second');

        $this->assertSame('https://example.com/second', $component->getUrl());
        $this->assertCount(1, $component->getAllProperties('URL'));
    }

    /** The setter must produce a real URL property, not just prime the getter. */
    /** @param callable(): (VEvent|VTodo|VJournal|VFreeBusy) $make */
    #[DataProvider('urlCapableComponentProvider')]
    public function testSetUrlAddsTheProperty(callable $make): void
    {
        $component = $make();
        $component->setUrl('https://example.com/thing');

        $property = $component->getProperty('URL');
        $this->assertNotNull($property);
        $this->assertSame('https://example.com/thing', $property->getValue()->getRawValue());
    }

    /** Reachable through the interface type, as the other setters are. */
    public function testUrlSurvivesWriteForVJournal(): void
    {
        $journal = new VJournal();
        $journal->setDtStamp('20240101T000000Z');
        $journal->setUid('test-uid');
        $journal->setUrl('https://example.com/journal');

        $this->assertStringContainsString(
            'URL:https://example.com/journal',
            (new Writer())->write($this->calendarWith($journal))
        );
    }

    public function testUrlSurvivesWriteForVFreeBusy(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addProperty(GenericProperty::create('DTSTAMP', '20240101T000000Z'));
        $freebusy->addProperty(GenericProperty::create('UID', 'test-uid'));
        $freebusy->setUrl('https://example.com/fb');

        $this->assertStringContainsString(
            'URL:https://example.com/fb',
            (new Writer())->write($this->calendarWith($freebusy))
        );
    }

    /** A URL set through the API must come back through a parse. */
    public function testUrlRoundTripsForVJournal(): void
    {
        $journal = new VJournal();
        $journal->setDtStamp('20240101T000000Z');
        $journal->setUid('test-uid');
        $journal->setUrl('https://example.com/journal');

        $ics = (new Writer())->write($this->calendarWith($journal));
        $reparsed = (new Parser(Parser::STRICT))->parse($ics);

        $this->assertSame(
            'https://example.com/journal',
            $reparsed->getComponents()[0]->getProperty('URL')?->getValue()->getRawValue()
        );
    }

    /** Regression: the components that already had these must be unaffected. */
    public function testExistingComponentsStillBehave(): void
    {
        foreach ([VEvent::class, VTodo::class] as $class) {
            $component = new $class();
            $this->assertNull($component->getUrl());
            $component->setUrl('https://example.com/x');
            $this->assertSame('https://example.com/x', $component->getUrl());
        }
    }

    private function calendarWith(ComponentInterface $component): \Icalendar\Component\VCalendar
    {
        $calendar = new \Icalendar\Component\VCalendar();
        $calendar->addProperty(GenericProperty::create('PRODID', '-//test//test//EN'));
        $calendar->addProperty(GenericProperty::create('VERSION', '2.0'));
        $calendar->addComponent($component);

        return $calendar;
    }
}
