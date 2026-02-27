<?php

declare(strict_types=1);

namespace Icalendar\Tests\Traits;

use Icalendar\Parser\Parser;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\AssertionFailedError;

trait RoundTripTestTrait
{
    protected Parser $roundTripParser;
    protected Writer $roundTripWriter;

    protected function setUpRoundTrip(): void
    {
        $this->roundTripParser = new Parser();
        $this->roundTripWriter = new Writer();
    }

    protected function assertRoundTripFidelity(string $icsPath, ?string $message = null): void
    {
        $this->setUpRoundTrip();
        
        $originalIcs = file_get_contents($icsPath);
        if ($originalIcs === false) {
            throw new AssertionFailedError("Failed to read ICS file: {$icsPath}");
        }

        $calendar = $this->roundTripParser->parse($originalIcs);
        $roundTripIcs = $this->roundTripWriter->write($calendar);

        $this->assertCalendarEquivalence($originalIcs, $roundTripIcs, $message ?? "Round-trip fidelity failed for: {$icsPath}");
    }

    protected function assertRoundTripFidelityFromString(string $originalIcs, ?string $message = null): void
    {
        $this->setUpRoundTrip();
        
        $calendar = $this->roundTripParser->parse($originalIcs);
        $roundTripIcs = $this->roundTripWriter->write($calendar);

        $this->assertCalendarEquivalence($originalIcs, $roundTripIcs, $message ?? 'Round-trip fidelity failed');
    }

    protected function assertCalendarEquivalence(string $originalIcs, string $roundTripIcs, string $message = ''): void
    {
        $this->assertValidIcsFormat($roundTripIcs, $message);
        
        $originalData = $this->extractCalendarData($originalIcs);
        $roundTripData = $this->extractCalendarData($roundTripIcs);

        $this->assertComponentCountsMatch($originalData, $roundTripData, $message);
        $this->assertRequiredPropertiesMatch($originalData, $roundTripData, $message);
    }

    protected function assertValidIcsFormat(string $ics, string $message): void
    {
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics, "{$message} - Missing VCALENDAR begin");
        $this->assertStringContainsString('END:VCALENDAR', $ics, "{$message} - Missing VCALENDAR end");
        $this->assertStringContainsString('VERSION:2.0', $ics, "{$message} - Missing VERSION");
        $this->assertStringContainsString('PRODID:', $ics, "{$message} - Missing PRODID");
    }

    protected function extractCalendarData(string $ics): array
    {
        $data = [];
        $data['components'] = [];
        $currentComponent = null;

        $lines = explode("\n", $this->normalizeIcs($ics));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            
            if (preg_match('/^BEGIN:(.+)$/', $line, $matches)) {
                $currentComponent = [
                    'type' => $matches[1],
                    'properties' => [],
                ];
            } elseif (preg_match('/^END:(.+)$/', $line, $matches)) {
                if ($currentComponent !== null) {
                    $data['components'][] = $currentComponent;
                    $currentComponent = null;
                }
            } elseif ($currentComponent !== null) {
                $prop = $this->parseProperty($line);
                if ($prop !== null) {
                    $currentComponent['properties'][] = $prop;
                }
            } else {
                if (preg_match('/^(VERSION|PRODID):(.+)$/', $line, $matches)) {
                    $data[$matches[1]] = $matches[2];
                }
            }
        }

        return $data;
    }

    protected function normalizeIcs(string $ics): string
    {
        $normalized = str_replace("\r\n", "\n", $ics);
        $normalized = str_replace("\r", "\n", $normalized);
        $normalized = preg_replace('/\n+/', "\n", $normalized);
        $normalized = trim($normalized);
        
        return $normalized;
    }

    protected function parseProperty(string $line): ?array
    {
        $colonPos = strpos($line, ':');
        if ($colonPos === false) {
            return null;
        }

        $nameAndParams = substr($line, 0, $colonPos);
        $value = substr($line, $colonPos + 1);

        $name = $nameAndParams;

        if (($semicolonPos = strpos($nameAndParams, ';')) !== false) {
            $name = substr($nameAndParams, 0, $semicolonPos);
        }

        return [
            'name' => strtoupper($name),
            'value' => $value,
        ];
    }

    protected function assertComponentCountsMatch(array $original, array $roundTrip, string $message): void
    {
        $originalCounts = [];
        foreach ($original['components'] as $component) {
            $type = $component['type'];
            $originalCounts[$type] = ($originalCounts[$type] ?? 0) + 1;
        }

        $roundTripCounts = [];
        foreach ($roundTrip['components'] as $component) {
            $type = $component['type'];
            $roundTripCounts[$type] = ($roundTripCounts[$type] ?? 0) + 1;
        }

        foreach ($originalCounts as $type => $count) {
            $this->assertArrayHasKey($type, $roundTripCounts, "{$message} - Missing component type: {$type}");
            $this->assertEquals($count, $roundTripCounts[$type], "{$message} - Component count mismatch for {$type}");
        }
    }

    protected function assertRequiredPropertiesMatch(array $original, array $roundTrip, string $message): void
    {
        foreach ($original['components'] as $origComponent) {
            $found = false;
            $origUid = $this->getPropertyValue($origComponent, 'UID');
            
            foreach ($roundTrip['components'] as $rtComponent) {
                if ($rtComponent['type'] !== $origComponent['type']) {
                    continue;
                }

                $rtUid = $this->getPropertyValue($rtComponent, 'UID');
                
                if ($origUid === $rtUid) {
                    $found = true;
                    $this->assertPropertiesMatch($origComponent, $rtComponent, $message);
                    break;
                }
            }

            $this->assertTrue($found, "{$message} - No matching component found for {$origComponent['type']} with UID {$origUid}");
        }
    }

    protected function assertPropertiesMatch(array $orig, array $rt, string $message): void
    {
        $ignoreProps = ['DTSTAMP', 'PRODID', 'CREATED', 'LAST-MODIFIED'];
        
        $origProps = [];
        foreach ($orig['properties'] as $prop) {
            if (!in_array($prop['name'], $ignoreProps, true)) {
                $origProps[$prop['name']] = $prop['value'];
            }
        }
        
        $rtProps = [];
        foreach ($rt['properties'] as $prop) {
            if (!in_array($prop['name'], $ignoreProps, true)) {
                $rtProps[$prop['name']] = $prop['value'];
            }
        }

        foreach ($origProps as $name => $value) {
            $this->assertArrayHasKey($name, $rtProps, "{$message} - Missing property {$name}");
            $this->assertEquals($value, $rtProps[$name], "{$message} - Property {$name} value mismatch");
        }
    }

    protected function getPropertyValue(array $component, string $name): ?string
    {
        foreach ($component['properties'] as $prop) {
            if ($prop['name'] === $name) {
                return $prop['value'];
            }
        }
        return null;
    }
}
