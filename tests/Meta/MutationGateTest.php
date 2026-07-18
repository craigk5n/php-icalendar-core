<?php

declare(strict_types=1);

namespace Icalendar\Tests\Meta;

use PHPUnit\Framework\TestCase;

/**
 * The mutation gate must keep counting uncovered code against the score.
 *
 * CI previously ran Infection with --only-covered, which *excludes* wholly
 * uncovered code from the score rather than penalising it. A class no test
 * reached therefore cost nothing, which is why the five dead Validation/Rule
 * classes in #19 survived a 2282-test suite and an 80% line-coverage gate
 * unnoticed. Measured on removal: 401 previously invisible mutants, and the
 * total MSI fell from 78% to 70.6%.
 *
 * The obvious way to make a red mutation build green again is to put the flag
 * back, which would silently restore the blind spot. This test makes that
 * choice fail out loud instead, mirroring how PhpVersionConsistencyTest and
 * CiTimezoneCoverageTest pin their own CI guarantees.
 */
class MutationGateTest extends TestCase
{
    private function ciWorkflow(): string
    {
        return (string) file_get_contents(__DIR__ . '/../../.github/workflows/ci.yml');
    }

    /** @return array<string, mixed> */
    private function infectionConfig(): array
    {
        $config = json_decode(
            (string) file_get_contents(__DIR__ . '/../../infection.json.dist'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        self::assertIsArray($config);

        return $config;
    }

    public function testOnlyCoveredIsNotReintroduced(): void
    {
        // Match the invocation itself, not the whole file: the surrounding
        // comment names the flag on purpose to explain why it is absent.
        self::assertSame(
            1,
            preg_match('/^\s*run:\s*(\S*infection\b.*)$/m', $this->ciWorkflow(), $m),
            'CI must still invoke infection'
        );

        self::assertStringNotContainsString(
            '--only-covered',
            $m[1],
            '--only-covered excludes uncovered code from mutation scoring instead of '
            . 'penalising it; that blind spot is what let #19 ship. Raise coverage or '
            . 'lower minMsi deliberately rather than restoring the flag.'
        );
    }

    /**
     * A total-score floor must exist and stay meaningful. Zero would disable the
     * gate while still appearing configured.
     */
    public function testTotalMsiFloorIsEnforced(): void
    {
        $config = $this->infectionConfig();

        self::assertArrayHasKey('minMsi', $config);
        self::assertIsNumeric($config['minMsi']);
        self::assertGreaterThanOrEqual(
            60,
            $config['minMsi'],
            'minMsi is the gate on untested code; keep it near the measured score'
        );
    }

    /**
     * Covered-code score must not be allowed to regress: it is the half of the
     * measurement that says whether the tests which *do* run assert anything.
     */
    public function testCoveredMsiFloorIsEnforced(): void
    {
        $config = $this->infectionConfig();

        self::assertArrayHasKey('minCoveredMsi', $config);
        self::assertIsNumeric($config['minCoveredMsi']);
        self::assertGreaterThanOrEqual(
            78,
            $config['minCoveredMsi'],
            'covered-code MSI measured 78%; lowering this lets tested code degrade'
        );
    }
}
