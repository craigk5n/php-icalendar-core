<?php

declare(strict_types=1);

namespace Icalendar\Tests\Validation;

use Icalendar\Validation\ErrorSeverity;
use PHPUnit\Framework\TestCase;

class ErrorSeverityTest extends TestCase
{
    public function testWarningConstant(): void
    {
        $this->assertEquals('WARNING', ErrorSeverity::WARNING->name);
    }

    public function testErrorConstant(): void
    {
        $this->assertEquals('ERROR', ErrorSeverity::ERROR->name);
    }

    public function testFatalConstant(): void
    {
        $this->assertEquals('FATAL', ErrorSeverity::FATAL->name);
    }

    public function testAllCasesExist(): void
    {
        $cases = ErrorSeverity::cases();
        
        $this->assertCount(3, $cases);
        $this->assertContains(ErrorSeverity::WARNING, $cases);
        $this->assertContains(ErrorSeverity::ERROR, $cases);
        $this->assertContains(ErrorSeverity::FATAL, $cases);
    }

    public function testCaseValues(): void
    {
        $this->assertEquals('WARNING', ErrorSeverity::WARNING->value);
        $this->assertEquals('ERROR', ErrorSeverity::ERROR->value);
        $this->assertEquals('FATAL', ErrorSeverity::FATAL->value);
    }

    public function testEnumIsBacked(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, ErrorSeverity::WARNING);
        $this->assertInstanceOf(\BackedEnum::class, ErrorSeverity::ERROR);
        $this->assertInstanceOf(\BackedEnum::class, ErrorSeverity::FATAL);
    }

    public function testFromName(): void
    {
        $warning = ErrorSeverity::from('WARNING');
        $this->assertSame(ErrorSeverity::WARNING, $warning);

        $error = ErrorSeverity::from('ERROR');
        $this->assertSame(ErrorSeverity::ERROR, $error);

        $fatal = ErrorSeverity::from('FATAL');
        $this->assertSame(ErrorSeverity::FATAL, $fatal);
    }

    public function testFromNameThrowsForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ErrorSeverity::from('INVALID');
    }

    public function testTryFrom(): void
    {
        $warning = ErrorSeverity::tryFrom('WARNING');
        $this->assertSame(ErrorSeverity::WARNING, $warning);

        $invalid = ErrorSeverity::tryFrom('INVALID');
        $this->assertNull($invalid);
    }

    public function testEnumComparison(): void
    {
        $this->assertTrue(ErrorSeverity::WARNING === ErrorSeverity::WARNING);
        $this->assertFalse(ErrorSeverity::WARNING === ErrorSeverity::ERROR);
        $this->assertTrue(ErrorSeverity::WARNING !== ErrorSeverity::ERROR);
    }

    public function testEnumInArray(): void
    {
        $severities = [ErrorSeverity::WARNING, ErrorSeverity::ERROR];
        
        $this->assertTrue(in_array(ErrorSeverity::WARNING, $severities));
        $this->assertTrue(in_array(ErrorSeverity::ERROR, $severities));
        $this->assertFalse(in_array(ErrorSeverity::FATAL, $severities));
    }
}