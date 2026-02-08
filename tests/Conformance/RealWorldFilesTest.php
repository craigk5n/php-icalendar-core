<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VCalendar;
use Icalendar\Parser\Parser;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\TestCase;

/**
 * Final robust test using real-world ICS files from major providers.
 * 
 * Verifies that the library can parse complex, real-world data and
 * maintain semantic integrity through a round-trip (parse → write → parse).
 */
class RealWorldFilesTest extends TestCase
{
    private Parser $parser;
    private Writer $writer;

    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->writer = new Writer();
    }

    /**
     * @dataProvider realWorldFileProvider
     */
    public function testRoundTrip(string $filename): void
    {
        $path = __DIR__ . '/../fixtures/real_world/' . $filename;
        $this->assertFileExists($path);
        
        $originalIcs = file_get_contents($path);
        
        // Step 1: Parse original (Lenient mode for real-world potential minor non-compliance)
        $this->parser->setStrict(false);
        $originalCalendar = $this->parser->parse($originalIcs);
        $this->assertNotNull($originalCalendar);

        // Step 2: Write back to ICS
        $exportedIcs = $this->writer->write($originalCalendar);
        $this->assertNotEmpty($exportedIcs);

        // Step 3: Parse the exported version
        // We should be able to parse our own output in STRICT mode
        $this->parser->setStrict(true);
        try {
            $reparsedCalendar = $this->parser->parse($exportedIcs);
        } catch (\Exception $e) {
            // If strict fails, fall back to lenient to see the diff, but fail the test
            $this->parser->setStrict(false);
            $reparsedCalendar = $this->parser->parse($exportedIcs);
            $this->fail("Failed to parse exported ICS in strict mode for $filename: " . $e->getMessage());
        }

        // Step 4: Verify Semantic Equivalence
        $this->assertCalendarsSemanticallyEquivalent($originalCalendar, $reparsedCalendar, $filename);
    }

    public static function realWorldFileProvider(): array
    {
        return [
            ['google_us_holidays.ics'],
            ['office_france_holidays.ics'],
            ['google_japan_holidays.ics'],
            ['office_germany_holidays.ics'],
            ['google_moon_phases.ics'],
            ['google_uk_holidays.ics'],
            ['google_canada_holidays.ics'],
            ['google_jewish_holidays.ics'],
            ['google_islamic_holidays.ics'],
            ['office_un_holidays.ics'],
        ];
    }

    private function assertCalendarsSemanticallyEquivalent(VCalendar $orig, VCalendar $reparsed, string $context): void
    {
        $this->assertComponentSemanticallyEquivalent($orig, $reparsed, $context);
    }

    private function assertComponentSemanticallyEquivalent(ComponentInterface $orig, ComponentInterface $reparsed, string $context): void
    {
        $this->assertEquals($orig->getName(), $reparsed->getName(), "Component name mismatch in $context");

        // Compare Properties
        $origProps = $orig->getProperties();
        $reparsedProps = $reparsed->getProperties();

        // Note: Some properties might be filtered or normalized by our writer (e.g. STYLED-DESCRIPTION logic)
        // But for these files, we expect them to mostly match.
        // We compare counts first.
        $this->assertCount(count($origProps), $reparsedProps, "Property count mismatch in component " . $orig->getName() . " of $context");

        foreach ($origProps as $i => $origProp) {
            $reparsedProp = $reparsedProps[$i];
            $this->assertEquals($origProp->getName(), $reparsedProp->getName(), "Property name mismatch at index $i in " . $orig->getName());
            
            // Value comparison (semantic)
            // We use getRawValue() for a reliable comparison of what was parsed
            $this->assertEquals(
                $origProp->getValue()->getRawValue(),
                $reparsedProp->getValue()->getRawValue(),
                "Value mismatch for property " . $origProp->getName() . " in " . $orig->getName()
            );

            // Parameter comparison
            $origParams = $origProp->getParameters();
            $reparsedParams = $reparsedProp->getParameters();
            
            // Canonicalize param names (casing doesn't matter in RFC 5545)
            $origParams = array_change_key_case($origParams, CASE_UPPER);
            $reparsedParams = array_change_key_case($reparsedParams, CASE_UPPER);
            
            $this->assertEquals($origParams, $reparsedParams, "Parameters mismatch for property " . $origProp->getName());
        }

        // Compare Sub-components
        $origSub = $orig->getComponents();
        $reparsedSub = $reparsed->getComponents();

        $this->assertCount(count($origSub), $reparsedSub, "Sub-component count mismatch in " . $orig->getName());

        foreach ($origSub as $i => $origC) {
            $this->assertComponentSemanticallyEquivalent($origC, $reparsedSub[$i], $context);
        }
    }
}
