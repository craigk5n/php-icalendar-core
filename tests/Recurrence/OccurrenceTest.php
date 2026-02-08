<?php

declare(strict_types=1);

namespace Icalendar\Tests\Recurrence;

use DateTimeImmutable;
use Icalendar\Recurrence\Occurrence;
use PHPUnit\Framework\TestCase;

class OccurrenceTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $start = new DateTimeImmutable('2026-01-01 09:00:00');
        $occurrence = new Occurrence($start);

        $this->assertSame($start, $occurrence->getStart());
        $this->assertNull($occurrence->getEnd());
        $this->assertFalse($occurrence->isRdate());
    }

    public function testConstructorWithAllParameters(): void
    {
        $start = new DateTimeImmutable('2026-01-01 09:00:00');
        $end = new DateTimeImmutable('2026-01-01 10:00:00');
        $occurrence = new Occurrence($start, $end, true);

        $this->assertSame($start, $occurrence->getStart());
        $this->assertSame($end, $occurrence->getEnd());
        $this->assertTrue($occurrence->isRdate());
    }

    public function testImmutability(): void
    {
        $start = new DateTimeImmutable('2026-01-01 09:00:00');
        $occurrence = new Occurrence($start);

        $this->assertSame($start, $occurrence->getStart());
        
        // Ensure it's the same instance as passed (since DateTimeImmutable is immutable itself)
        $this->assertSame($start, $occurrence->getStart());
    }
}
