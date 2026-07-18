<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\VEvent;
use Icalendar\Component\VJournal;
use Icalendar\Component\VTodo;
use Icalendar\Component\ComponentInterface;
use Icalendar\Parser\Parser;
use Icalendar\Writer\PropertyWriter;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * CATEGORIES is a comma-separated list of TEXT values (RFC 5545 §3.8.1.2):
 *
 *   categories = "CATEGORIES" catparam ":" text *("," text) CRLF
 *
 * The separator commas are literal; only a comma appearing *inside* an
 * individual category name is escaped (§3.3.11). The old implementation joined
 * the values into one TEXT value before writing, so TextWriter escaped every
 * comma -- including the separators -- and a strict or foreign parser read the
 * whole line as a single category. These tests pin the list semantics: separator
 * commas stay literal, in-value specials get escaped, and the two cases stay
 * distinguishable through a full write/parse round trip.
 */
class CategoriesListTest extends TestCase
{
    /**
     * @return array<string, array{callable(): ComponentInterface}>
     */
    public static function componentProvider(): array
    {
        return [
            'VEVENT' => [static fn (): ComponentInterface => new VEvent()],
            'VTODO' => [static fn (): ComponentInterface => new VTodo()],
            'VJOURNAL' => [static fn (): ComponentInterface => new VJournal()],
        ];
    }

    private function writeCategories(ComponentInterface $component): string
    {
        $prop = $component->getProperty('CATEGORIES');
        self::assertNotNull($prop);

        return (new PropertyWriter())->write($prop);
    }

    /**
     * @param callable(): ComponentInterface $factory
     */
    #[DataProvider('componentProvider')]
    public function testSeparatorCommasAreLiteral(callable $factory): void
    {
        $component = $factory();
        $component->setCategories('git', 'release');

        self::assertSame('CATEGORIES:git,release', $this->writeCategories($component));
    }

    /**
     * A single category that happens to contain a comma must be escaped so it
     * survives as one value -- the case the old code could not distinguish from
     * a two-item list.
     *
     * @param callable(): ComponentInterface $factory
     */
    #[DataProvider('componentProvider')]
    public function testCommaInsideACategoryIsEscaped(callable $factory): void
    {
        $component = $factory();
        $component->setCategories('release, urgent');

        self::assertSame('CATEGORIES:release\\, urgent', $this->writeCategories($component));
        self::assertSame(['release, urgent'], $component->getCategories());
    }

    /**
     * Semicolons and backslashes inside a category are TEXT specials and must be
     * escaped per §3.3.11, while the list separator stays literal.
     *
     * @param callable(): ComponentInterface $factory
     */
    #[DataProvider('componentProvider')]
    public function testSemicolonAndBackslashInsideACategoryAreEscaped(callable $factory): void
    {
        $component = $factory();
        $component->setCategories('a;b', 'c\\d');

        self::assertSame('CATEGORIES:a\\;b,c\\\\d', $this->writeCategories($component));
    }

    /**
     * The reported round-trip masking: a genuine two-item list must reparse as
     * two categories, not one.
     *
     * @param callable(): ComponentInterface $factory
     */
    #[DataProvider('componentProvider')]
    public function testMultiValueListRoundTripsAsTwoCategories(callable $factory): void
    {
        $original = $this->buildCalendarWith($factory(), ['git', 'release']);

        $reparsed = $this->roundTrip($original);

        self::assertSame(['git', 'release'], $reparsed->getCategories());
    }

    /**
     * The counterpart: a single category containing a literal comma must reparse
     * as exactly one category, proving the two cases are no longer conflated.
     *
     * @param callable(): ComponentInterface $factory
     */
    #[DataProvider('componentProvider')]
    public function testCommaCategoryRoundTripsAsOneCategory(callable $factory): void
    {
        $original = $this->buildCalendarWith($factory(), ['release, urgent']);

        $reparsed = $this->roundTrip($original);

        self::assertSame(['release, urgent'], $reparsed->getCategories());
    }

    /**
     * Existing single-value behaviour must be unchanged.
     *
     * @param callable(): ComponentInterface $factory
     */
    #[DataProvider('componentProvider')]
    public function testSingleCategoryIsUnescaped(callable $factory): void
    {
        $component = $factory();
        $component->setCategories('Work');

        self::assertSame('CATEGORIES:Work', $this->writeCategories($component));
    }

    /**
     * @param list<string> $categories
     */
    private function buildCalendarWith(ComponentInterface $component, array $categories): ComponentInterface
    {
        $component->setUid('cat@example.com');
        $component->setDtStamp('20260101T000000Z');
        if ($component instanceof VEvent || $component instanceof VTodo) {
            $component->setDtStart('20260101T000000Z');
        }
        $component->setSummary('s');
        $component->setCategories(...$categories);

        return $component;
    }

    private function roundTrip(ComponentInterface $component): ComponentInterface
    {
        $calendar = new \Icalendar\Component\VCalendar();
        $calendar->setProductId('-//test//test//EN')->setVersion('2.0');
        $calendar->addComponent($component);

        $ics = (new Writer())->write($calendar);
        $parsed = (new Parser())->parse($ics);

        $components = $parsed->getComponents($component->getName());
        self::assertNotEmpty($components);
        $first = $components[0];
        self::assertInstanceOf(ComponentInterface::class, $first);

        return $first;
    }
}
