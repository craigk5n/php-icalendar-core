<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Parser\Parser;
use Icalendar\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every fixture under tests/fixtures/rfc5545/ must satisfy the library's own
 * Validator.
 *
 * Nothing checked this, and alarm-email.ics did not: its VALARM used
 * ACTION:EMAIL while carrying the ATTENDEE on the enclosing VEVENT. RFC 5545
 * §3.6.6 requires ATTENDEE *within* the alarm -- an EMAIL alarm needs to know
 * who to mail, and the event's attendee list is a different thing. So a file
 * shipped as an RFC example was non-conformant, and the round-trip tests using
 * it were asserting against invalid input.
 *
 * These fixtures are the reference for what the library considers correct, so
 * they are worth holding to the standard they are named after.
 */
class FixtureConformanceTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function rfc5545FixtureProvider(): array
    {
        $cases = [];
        foreach (glob(__DIR__ . '/../fixtures/rfc5545/*.ics') ?: [] as $path) {
            $cases[basename($path)] = [$path];
        }

        return $cases;
    }

    #[DataProvider('rfc5545FixtureProvider')]
    public function testFixtureIsValid(string $path): void
    {
        $calendar = (new Parser(Parser::STRICT))->parse((string) file_get_contents($path));

        $validator = new Validator();
        $messages = array_map(
            static fn (object $error): string => $error->message,
            $validator->validateAsArray($calendar)
        );

        $this->assertTrue(
            $validator->isValid($calendar),
            basename($path) . ' is not RFC 5545 conformant: ' . implode('; ', $messages)
        );
    }

    /** The specific regression: an EMAIL alarm carries its own ATTENDEE. */
    public function testEmailAlarmHasAttendeeWithinTheAlarm(): void
    {
        $calendar = (new Parser(Parser::STRICT))->parse(
            (string) file_get_contents(__DIR__ . '/../fixtures/rfc5545/alarm-email.ics')
        );

        $alarm = $calendar->getComponents()[0]->getComponents('VALARM')[0];

        $this->assertSame('EMAIL', $alarm->getProperty('ACTION')?->getValue()->getRawValue());
        $this->assertNotNull(
            $alarm->getProperty('ATTENDEE'),
            'RFC 5545 §3.6.6: an EMAIL alarm requires ATTENDEE within the VALARM'
        );
    }
}
