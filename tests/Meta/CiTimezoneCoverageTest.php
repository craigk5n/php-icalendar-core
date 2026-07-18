<?php

declare(strict_types=1);

namespace Icalendar\Tests\Meta;

use PHPUnit\Framework\TestCase;

/**
 * CI must run the suite under at least one non-UTC timezone.
 *
 * The library hands out DateTimeImmutables and parses floating, UTC and zoned
 * DATE-TIME forms, so host-timezone coupling is a live risk (issue #17 fixed
 * three tests that passed on UTC and failed elsewhere). A UTC-only matrix can
 * never catch that class of bug -- UTC is the default on most CI runners and
 * containers, i.e. the one clock under which such coupling is invisible.
 *
 * This test pins that guarantee to the workflow file so a future edit that drops
 * the non-UTC job trips a red test rather than silently narrowing coverage,
 * mirroring PhpVersionConsistencyTest's guard over the PHP matrix.
 */
class CiTimezoneCoverageTest extends TestCase
{
    public function testCiRunsUnderANonUtcTimezone(): void
    {
        $ci = (string) file_get_contents(__DIR__ . '/../../.github/workflows/ci.yml');

        self::assertSame(
            1,
            preg_match('/date\.timezone\s*=\s*(\S+)/', $ci, $m),
            'CI workflow must set date.timezone for at least one job so a non-UTC host is exercised'
        );

        $timezone = $m[1];
        self::assertNotSame(
            'UTC',
            strtoupper($timezone),
            "CI's configured date.timezone ({$timezone}) must not be UTC, or host-timezone coupling stays invisible"
        );

        // The value must be a timezone PHP actually recognises, otherwise the job
        // would silently fall back to its default and the guarantee is hollow.
        self::assertTrue(
            in_array($timezone, \DateTimeZone::listIdentifiers(), true),
            "CI's configured date.timezone ({$timezone}) is not a valid PHP timezone identifier"
        );
    }
}
