<?php

declare(strict_types=1);

namespace Icalendar\Tests\Recurrence;

use Icalendar\Exception\ParseException;
use Icalendar\Recurrence\RRuleParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * RRuleParser must accept the same rules in both modes.
 *
 * validateRules() ran only when strict, while buildRRule() coerced regardless,
 * so lenient mode silently invented values: FREQ=NONSENSE was accepted as-is
 * and INTERVAL=abc became INTERVAL=0 via a bare (int) cast. Mode governs how a
 * failure is reported, not what counts as a valid rule.
 */
class RRuleParserModeParityTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function invalidRuleProvider(): array
    {
        return [
            'unknown freq' => ['FREQ=NONSENSE'],
            'empty freq' => ['FREQ='],
            'missing freq' => ['COUNT=5'],
            'non-numeric interval' => ['FREQ=DAILY;INTERVAL=abc'],
            'zero interval' => ['FREQ=DAILY;INTERVAL=0'],
            'negative interval' => ['FREQ=DAILY;INTERVAL=-5'],
            'non-numeric count' => ['FREQ=DAILY;COUNT=abc'],
            'zero count' => ['FREQ=DAILY;COUNT=0'],
            'negative count' => ['FREQ=DAILY;COUNT=-5'],
            'bymonth out of range' => ['FREQ=YEARLY;BYMONTH=13'],
            'bymonth zero' => ['FREQ=YEARLY;BYMONTH=0'],
            'bymonthday out of range' => ['FREQ=MONTHLY;BYMONTHDAY=32'],
            'bymonthday zero' => ['FREQ=MONTHLY;BYMONTHDAY=0'],
            'byhour out of range' => ['FREQ=DAILY;BYHOUR=24'],
            'byminute out of range' => ['FREQ=DAILY;BYMINUTE=60'],
            'bysetpos zero' => ['FREQ=MONTHLY;BYSETPOS=0'],
            'invalid wkst' => ['FREQ=WEEKLY;WKST=XX'],
            'both until and count' => ['FREQ=DAILY;UNTIL=20240101T000000Z;COUNT=5'],
        ];
    }

    /**
     * BYDAY had no validateRules() coverage at all, so unparseable values were
     * silently discarded by buildRRule() in *both* modes: 'FREQ=WEEKLY;BYDAY=GARBAGE'
     * became a bare 'FREQ=WEEKLY', turning "weekly on given days" into "every
     * day" without a word to the caller.
     *
     * @return array<string, array{string}>
     */
    public static function invalidByDayProvider(): array
    {
        return [
            'garbage byday' => ['FREQ=WEEKLY;BYDAY=GARBAGE'],
            'garbage among valid' => ['FREQ=WEEKLY;BYDAY=MO,GARBAGE,WE'],
            'bad day code' => ['FREQ=WEEKLY;BYDAY=9XX'],
            'zero ordinal' => ['FREQ=WEEKLY;BYDAY=0MO'],
        ];
    }

    #[DataProvider('invalidRuleProvider')]
    #[DataProvider('invalidByDayProvider')]
    public function testStrictModeRejects(string $rule): void
    {
        $parser = new RRuleParser();
        $parser->setStrict(true);

        $this->expectException(ParseException::class);
        $parser->parse($rule);
    }

    #[DataProvider('invalidRuleProvider')]
    #[DataProvider('invalidByDayProvider')]
    public function testLenientModeRejects(string $rule): void
    {
        $parser = new RRuleParser();
        $parser->setStrict(false);

        $this->expectException(ParseException::class);
        $parser->parse($rule);
    }

    /** Regression: INTERVAL=abc must never silently become INTERVAL=0. */
    public function testInvalidIntervalIsNotCoercedToZero(): void
    {
        $parser = new RRuleParser();
        $parser->setStrict(false);

        try {
            $rule = $parser->parse('FREQ=DAILY;INTERVAL=abc');
            $this->fail(
                'INTERVAL=abc was accepted and coerced to: ' . $rule->toString()
            );
        } catch (ParseException) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * Regression: a BYDAY the parser cannot read must not silently widen the
     * rule to every day of the week.
     */
    public function testUnparseableByDayIsNotSilentlyDropped(): void
    {
        $parser = new RRuleParser();
        $parser->setStrict(true);

        try {
            $rule = $parser->parse('FREQ=WEEKLY;BYDAY=GARBAGE');
            $this->fail('BYDAY=GARBAGE was silently dropped, yielding: ' . $rule->toString());
        } catch (ParseException) {
            $this->addToAssertionCount(1);
        }
    }

    /** @return array<string, array{string}> */
    public static function validRuleProvider(): array
    {
        return [
            'weekly byday' => ['FREQ=WEEKLY;BYDAY=MO,WE'],
            'daily count' => ['FREQ=DAILY;COUNT=10'],
            'monthly last day' => ['FREQ=MONTHLY;BYMONTHDAY=-1'],
            'monthly nth weekday' => ['FREQ=MONTHLY;BYDAY=2FR'],
            'monthly last weekday' => ['FREQ=MONTHLY;BYDAY=-1FR'],
            'signed ordinal' => ['FREQ=MONTHLY;BYDAY=+1MO'],
            'interval and wkst' => ['FREQ=WEEKLY;INTERVAL=2;WKST=SU;BYDAY=TU,TH'],
            'until' => ['FREQ=DAILY;UNTIL=20240101T000000Z'],
            'yearly bymonth' => ['FREQ=YEARLY;BYMONTH=1;BYDAY=SU'],
            'bysetpos' => ['FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1'],
            'boundary bymonth' => ['FREQ=YEARLY;BYMONTH=12'],
            'boundary byhour' => ['FREQ=DAILY;BYHOUR=23'],
            'leap second bysecond' => ['FREQ=DAILY;BYSECOND=60'],
        ];
    }

    /** Valid rules must survive untouched in both modes. */
    #[DataProvider('validRuleProvider')]
    public function testValidRulesParseInBothModes(string $rule): void
    {
        foreach ([true, false] as $strict) {
            $parser = new RRuleParser();
            $parser->setStrict($strict);

            $parsed = $parser->parse($rule);
            $this->assertNotSame('', $parsed->toString());
        }
    }

    /**
     * Unknown RRULE parts stay tolerated in lenient mode. Ignoring an
     * unrecognised extension is not the same defect as fabricating a value, and
     * real-world producers do emit non-standard parts.
     */
    public function testUnknownPartIsToleratedInLenientModeOnly(): void
    {
        $lenient = new RRuleParser();
        $lenient->setStrict(false);
        $this->assertSame('FREQ=DAILY', $lenient->parse('FREQ=DAILY;X-CUSTOM=1')->toString());

        $strict = new RRuleParser();
        $strict->setStrict(true);
        $this->expectException(ParseException::class);
        $strict->parse('FREQ=DAILY;X-CUSTOM=1');
    }
}
