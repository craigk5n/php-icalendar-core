<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Parser\ValueParser\DateValidator;
use PHPUnit\Framework\TestCase;

class DateValidatorTest extends TestCase
{
    // ========== isValidDate Tests ==========

    public function testIsValidDateNormalCases(): void
    {
        // Valid dates
        $this->assertTrue(DateValidator::isValidDate(2026, 2, 6));
        $this->assertTrue(DateValidator::isValidDate(2024, 2, 29)); // Leap year
        $this->assertTrue(DateValidator::isValidDate(2000, 2, 29)); // Century leap year
        $this->assertTrue(DateValidator::isValidDate(2023, 12, 31));
        $this->assertTrue(DateValidator::isValidDate(2023, 1, 1));
        $this->assertTrue(DateValidator::isValidDate(2023, 4, 30)); // 30-day month
        $this->assertTrue(DateValidator::isValidDate(2023, 1, 31)); // 31-day month
        
        // Invalid dates
        $this->assertFalse(DateValidator::isValidDate(2023, 2, 29)); // Non-leap year
        $this->assertFalse(DateValidator::isValidDate(1900, 2, 29)); // Century non-leap year
        $this->assertFalse(DateValidator::isValidDate(2023, 4, 31)); // April has 30 days
        $this->assertFalse(DateValidator::isValidDate(2023, 6, 31)); // June has 30 days
        $this->assertFalse(DateValidator::isValidDate(2023, 9, 31)); // September has 30 days
        $this->assertFalse(DateValidator::isValidDate(2023, 11, 31)); // November has 30 days
        $this->assertFalse(DateValidator::isValidDate(2023, 2, 30)); // February never has 30 days
        $this->assertFalse(DateValidator::isValidDate(2023, 4, 0)); // Day 0
        $this->assertFalse(DateValidator::isValidDate(2023, 4, 32)); // Day 32
    }

    public function testIsValidDateEdgeCases(): void
    {
        // Month boundaries
        $this->assertFalse(DateValidator::isValidDate(2023, 0, 15)); // Month 0
        $this->assertFalse(DateValidator::isValidDate(2023, 13, 15)); // Month 13
        $this->assertFalse(DateValidator::isValidDate(2023, -1, 15)); // Negative month
        $this->assertFalse(DateValidator::isValidDate(2023, 14, 15)); // Month > 12
        
        // Day boundaries
        $this->assertFalse(DateValidator::isValidDate(2023, 6, 0)); // Day 0
        $this->assertFalse(DateValidator::isValidDate(2023, 6, -1)); // Negative day
        $this->assertFalse(DateValidator::isValidDate(2023, 6, 32)); // Day > 31
        
        // Year boundaries
        $this->assertTrue(DateValidator::isValidDate(1, 1, 1)); // Minimum realistic year
        $this->assertTrue(DateValidator::isValidDate(9999, 12, 31)); // High year
        $this->assertTrue(DateValidator::isValidDate(0, 1, 1)); // Year 0 (PHP allows this)
        $this->assertTrue(DateValidator::isValidDate(-1, 1, 1)); // Negative year (PHP allows this)
    }

    public function testIsValidDateMonthVariations(): void
    {
        // Test all months with valid days
        $this->assertTrue(DateValidator::isValidDate(2023, 1, 31)); // January - 31 days
        $this->assertTrue(DateValidator::isValidDate(2023, 2, 28)); // February - 28 days (non-leap)
        $this->assertTrue(DateValidator::isValidDate(2023, 3, 31)); // March - 31 days
        $this->assertTrue(DateValidator::isValidDate(2023, 4, 30)); // April - 30 days
        $this->assertTrue(DateValidator::isValidDate(2023, 5, 31)); // May - 31 days
        $this->assertTrue(DateValidator::isValidDate(2023, 6, 30)); // June - 30 days
        $this->assertTrue(DateValidator::isValidDate(2023, 7, 31)); // July - 31 days
        $this->assertTrue(DateValidator::isValidDate(2023, 8, 31)); // August - 31 days
        $this->assertTrue(DateValidator::isValidDate(2023, 9, 30)); // September - 30 days
        $this->assertTrue(DateValidator::isValidDate(2023, 10, 31)); // October - 31 days
        $this->assertTrue(DateValidator::isValidDate(2023, 11, 30)); // November - 30 days
        $this->assertTrue(DateValidator::isValidDate(2023, 12, 31)); // December - 31 days
        
        // Test invalid days for each month
        $this->assertFalse(DateValidator::isValidDate(2023, 1, 32)); // January
        $this->assertFalse(DateValidator::isValidDate(2023, 2, 29)); // February (non-leap)
        $this->assertFalse(DateValidator::isValidDate(2023, 3, 32)); // March
        $this->assertFalse(DateValidator::isValidDate(2023, 4, 31)); // April
        $this->assertFalse(DateValidator::isValidDate(2023, 5, 32)); // May
        $this->assertFalse(DateValidator::isValidDate(2023, 6, 31)); // June
        $this->assertFalse(DateValidator::isValidDate(2023, 7, 32)); // July
        $this->assertFalse(DateValidator::isValidDate(2023, 8, 32)); // August
        $this->assertFalse(DateValidator::isValidDate(2023, 9, 31)); // September
        $this->assertFalse(DateValidator::isValidDate(2023, 10, 32)); // October
        $this->assertFalse(DateValidator::isValidDate(2023, 11, 31)); // November
        $this->assertFalse(DateValidator::isValidDate(2023, 12, 32)); // December
    }

    // ========== isLeapYear Tests ==========

    public function testIsLeapYear(): void
    {
        // Leap years
        $this->assertTrue(DateValidator::isLeapYear(2024)); // Divisible by 4, not by 100
        $this->assertTrue(DateValidator::isLeapYear(2020)); // Divisible by 4, not by 100
        $this->assertTrue(DateValidator::isLeapYear(2000)); // Divisible by 400
        $this->assertTrue(DateValidator::isLeapYear(1600)); // Divisible by 400
        $this->assertTrue(DateValidator::isLeapYear(2400)); // Divisible by 400
        
        // Non-leap years
        $this->assertFalse(DateValidator::isLeapYear(2023)); // Not divisible by 4
        $this->assertFalse(DateValidator::isLeapYear(2021)); // Not divisible by 4
        $this->assertFalse(DateValidator::isLeapYear(1900)); // Divisible by 100, not by 400
        $this->assertFalse(DateValidator::isLeapYear(2100)); // Divisible by 100, not by 400
        $this->assertFalse(DateValidator::isLeapYear(1800)); // Divisible by 100, not by 400
        
        // Edge cases
        $this->assertFalse(DateValidator::isLeapYear(0)); // Year 0 (divisible by 100, not by 400)
        $this->assertTrue(DateValidator::isLeapYear(4)); // Smallest positive leap year
        $this->assertFalse(DateValidator::isLeapYear(1)); // Smallest positive non-leap year
        $this->assertFalse(DateValidator::isLeapYear(-1)); // Negative year test
        $this->assertTrue(DateValidator::isLeapYear(-4)); // Negative leap year
    }

    public function testIsLeapYearCenturyRules(): void
    {
        // Test century years specifically
        for ($year = 1600; $year <= 2400; $year += 100) {
            if ($year % 400 === 0) {
                $this->assertTrue(DateValidator::isLeapYear($year), "Year $year should be a leap year (divisible by 400)");
            } else {
                $this->assertFalse(DateValidator::isLeapYear($year), "Year $year should not be a leap year (century not divisible by 400)");
            }
        }
    }

    // ========== Integration Tests ==========

    public function testLeapYearDateValidation(): void
    {
        // Test February 29th in various years
        $leapYears = [2024, 2020, 2000, 1996, 1992, 1600, 2400];
        $nonLeapYears = [2023, 2022, 2021, 2019, 2018, 1900, 2100];
        
        foreach ($leapYears as $year) {
            $this->assertTrue(
                DateValidator::isValidDate($year, 2, 29),
                "Year $year is a leap year, Feb 29 should be valid"
            );
        }
        
        foreach ($nonLeapYears as $year) {
            $this->assertFalse(
                DateValidator::isValidDate($year, 2, 29),
                "Year $year is not a leap year, Feb 29 should be invalid"
            );
        }
    }

    public function testDateValidationWithLeapYear(): void
    {
        // Test all February dates in leap year vs non-leap year
        for ($day = 28; $day <= 29; $day++) {
            $this->assertTrue(DateValidator::isValidDate(2024, 2, $day)); // Leap year
            if ($day === 29) {
                $this->assertFalse(DateValidator::isValidDate(2023, 2, $day)); // Non-leap year
            } else {
                $this->assertTrue(DateValidator::isValidDate(2023, 2, $day)); // Non-leap year
            }
        }
    }

    public function testDateValidationRange(): void
    {
        // Test some valid dates across the range
        $testDates = [
            [1, 1, 1],
            [100, 6, 15],
            [1900, 12, 31],
            [2000, 2, 29], // Leap year
            [2023, 2, 28], // Non-leap year
            [9999, 12, 31]
        ];
        
        foreach ($testDates as [$year, $month, $day]) {
            $this->assertTrue(
                DateValidator::isValidDate($year, $month, $day),
                "Date {$year}-{$month}-{$day} should be valid"
            );
        }
    }
}