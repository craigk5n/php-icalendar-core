<?php

declare(strict_types=1);

namespace Icalendar\Tests\Meta;

use PHPUnit\Framework\TestCase;

/**
 * The declared minimum PHP version must agree across every source that states it.
 *
 * composer.json said ">=8.1" (lowered deliberately in #7) while README and
 * STATUS still said "8.2+" and the CI matrix only ran 8.2-8.4 -- so the floor
 * was never actually tested and the docs contradicted the manifest. This test
 * derives the minimum from composer.json (the manifest that Composer enforces)
 * and holds the others to it, so the three can no longer drift apart.
 */
class PhpVersionConsistencyTest extends TestCase
{
    /** @return string e.g. "8.1" */
    private function composerMinPhp(): string
    {
        $composer = json_decode(
            (string) file_get_contents(__DIR__ . '/../../composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        self::assertIsArray($composer);
        self::assertIsArray($composer['require'] ?? null);
        $constraint = $composer['require']['php'] ?? '';
        self::assertIsString($constraint);

        // ">=8.1", ">=8.1.0", "^8.1" -> "8.1"
        self::assertSame(1, preg_match('/(\d+\.\d+)/', $constraint, $m));

        return $m[1];
    }

    public function testComposerDeclaresAMinimum(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+$/', $this->composerMinPhp());
    }

    /** The CI matrix must exercise the declared floor, or the claim is untested. */
    public function testCiMatrixIncludesTheMinimum(): void
    {
        $min = $this->composerMinPhp();
        $ci = (string) file_get_contents(__DIR__ . '/../../.github/workflows/ci.yml');

        $this->assertMatchesRegularExpression(
            "/php:\\s*\\[[^\\]]*'" . preg_quote($min, '/') . "'/",
            $ci,
            "CI test matrix must include PHP {$min} so the declared minimum is actually run"
        );
    }

    public function testReadmeStatesTheMinimum(): void
    {
        $min = $this->composerMinPhp();
        $readme = (string) file_get_contents(__DIR__ . '/../../README.md');

        $this->assertStringContainsString(
            $min,
            $readme,
            "README must reference the declared minimum PHP {$min}"
        );
        // Guard against a stale higher floor lingering in the prose.
        $this->assertStringNotContainsString(
            'Requires PHP 8.2+',
            $readme,
            'README still advertises 8.2+ while composer.json allows a lower floor'
        );
    }

    public function testStatusStatesTheMinimum(): void
    {
        $min = $this->composerMinPhp();
        $status = (string) file_get_contents(__DIR__ . '/../../STATUS.md');

        $this->assertStringContainsString("PHP {$min}+", $status);
    }
}
