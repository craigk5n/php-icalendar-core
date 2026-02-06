<?php

declare(strict_types=1);

namespace Icalendar\Tests\Value;

use Icalendar\Value\TextValue;
use PHPUnit\Framework\TestCase;

class TextValueTest extends TestCase
{
    public function testConstructor(): void
    {
        $value = new TextValue('Test text');
        $this->assertEquals('Test text', $value->getRawValue());
    }

    public function testGetTypeReturnsText(): void
    {
        $value = new TextValue('Test');
        $this->assertEquals('TEXT', $value->getType());
    }

    public function testGetRawValue(): void
    {
        $text = 'Test text value';
        $value = new TextValue($text);
        $this->assertEquals($text, $value->getRawValue());
    }

    public function testSerialize(): void
    {
        $text = 'Test text value';
        $value = new TextValue($text);
        $this->assertEquals($text, $value->serialize());
    }

    public function testIsDefaultReturnsTrue(): void
    {
        $value = new TextValue('Test');
        $this->assertTrue($value->isDefault());
    }

    public function testEmptyString(): void
    {
        $value = new TextValue('');
        $this->assertEquals('', $value->getRawValue());
        $this->assertEquals('', $value->serialize());
    }

    public function testUnicodeText(): void
    {
        $text = 'Hello 世界 🌍';
        $value = new TextValue($text);
        $this->assertEquals($text, $value->getRawValue());
        $this->assertEquals($text, $value->serialize());
    }

    public function testSpecialCharacters(): void
    {
        $text = 'Test with "quotes" and\nnewlines\ttabs';
        $value = new TextValue($text);
        $this->assertEquals($text, $value->getRawValue());
        $this->assertEquals($text, $value->serialize());
    }
}