<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\GeoParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * GEO is a structured value: two ";"-separated FLOATs (RFC 5545 §3.8.1.6),
 * `GEO:37.386013;-122.082932`, with latitude in [-90, 90] and longitude in
 * [-180, 180]. It was never in the parser's property map, so it fell through to
 * TEXT and `GEO:total garbage` parsed clean. Mapping it to FLOAT is wrong -- the
 * real form has a semicolon FloatParser rejects -- so it needs its own parser.
 */
class GeoParserTest extends TestCase
{
    private GeoParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new GeoParser();
    }

    /** @return array<string, array{string}> */
    public static function validProvider(): array
    {
        return [
            'typical' => ['37.386013;-122.082932'],
            'integers' => ['37;-122'],
            'zero' => ['0;0'],
            'north-east extremes' => ['90;180'],
            'south-west extremes' => ['-90;-180'],
            'explicit plus signs' => ['+12.5;+13.5'],
        ];
    }

    #[DataProvider('validProvider')]
    public function testValidGeoParsesToItsCanonicalValue(string $value): void
    {
        self::assertSame($value, $this->parser->parse($value));
        self::assertTrue($this->parser->canParse($value));
    }

    public function testSurroundingWhitespaceIsTrimmed(): void
    {
        self::assertSame('37.386013;-122.082932', $this->parser->parse('  37.386013;-122.082932  '));
    }

    /** @return array<string, array{string}> */
    public static function invalidProvider(): array
    {
        return [
            'garbage' => ['total garbage'],
            'single float, no separator' => ['37.386013'],
            'three components' => ['1;2;3'],
            'empty' => [''],
            'only separator' => [';'],
            'non-float latitude' => ['abc;12'],
            'non-float longitude' => ['12;abc'],
            'latitude above range' => ['90.1;0'],
            'latitude below range' => ['-90.1;0'],
            'longitude above range' => ['0;180.1'],
            'longitude below range' => ['0;-180.1'],
            'comma-separated (wrong delimiter)' => ['37.386013,-122.082932'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidGeoThrows(string $value): void
    {
        self::assertFalse($this->parser->canParse($value));

        $this->expectException(ParseException::class);
        $this->parser->parse($value);
    }

    public function testInvalidGeoUsesTheGeoErrorCode(): void
    {
        try {
            $this->parser->parse('91;0');
            self::fail('expected ParseException');
        } catch (ParseException $e) {
            self::assertSame(ParseException::ERR_INVALID_GEO, $e->getErrorCode());
        }
    }

    public function testTypeIsGeo(): void
    {
        self::assertSame('GEO', $this->parser->getType());
    }
}
