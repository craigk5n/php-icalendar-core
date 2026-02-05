<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\ValueParserFactory;
use Icalendar\Parser\ValueParser\ValueParserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ValueParserFactory class
 */
class ValueParserFactoryTest extends TestCase
{
    private ValueParserFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ValueParserFactory();
    }

    protected function tearDown(): void
    {
        $this->factory->clearCache();
    }

    public function testFactoryReturnsDateParser(): void
    {
        $parser = $this->factory->getParser('DATE');

        $this->assertInstanceOf(ValueParserInterface::class, $parser);
        $this->assertEquals('DATE', $parser->getType());
    }

    public function testFactoryReturnsDateTimeParser(): void
    {
        $parser = $this->factory->getParser('DATE-TIME');

        $this->assertInstanceOf(ValueParserInterface::class, $parser);
        $this->assertEquals('DATE-TIME', $parser->getType());
    }

    public function testFactoryReturnsTextParser(): void
    {
        $parser = $this->factory->getParser('TEXT');

        $this->assertInstanceOf(ValueParserInterface::class, $parser);
        $this->assertEquals('TEXT', $parser->getType());
    }

    public function testFactoryReturnsIntegerParser(): void
    {
        $parser = $this->factory->getParser('INTEGER');

        $this->assertInstanceOf(ValueParserInterface::class, $parser);
        $this->assertEquals('INTEGER', $parser->getType());
    }

    public function testFactoryReturnsDurationParser(): void
    {
        $parser = $this->factory->getParser('DURATION');

        $this->assertInstanceOf(ValueParserInterface::class, $parser);
        $this->assertEquals('DURATION', $parser->getType());
    }

    public function testFactoryReturnsUriParser(): void
    {
        $parser = $this->factory->getParser('URI');

        $this->assertInstanceOf(ValueParserInterface::class, $parser);
        $this->assertEquals('URI', $parser->getType());
    }

    public function testFactoryUsesValueParameter(): void
    {
        // DTSTART normally defaults to DATE-TIME, but VALUE=DATE should override
        $parser = $this->factory->getParserForProperty('DTSTART', ['VALUE' => 'DATE']);

        $this->assertEquals('DATE', $parser->getType());
    }

    public function testFactoryUsesValueParameterCaseInsensitive(): void
    {
        $parser = $this->factory->getParserForProperty('DTSTART', ['VALUE' => 'date']);

        $this->assertEquals('DATE', $parser->getType());
    }

    public function testFactoryDefaultsToText(): void
    {
        // Unknown property should default to TEXT
        $parser = $this->factory->getParserForProperty('X-CUSTOM-PROPERTY', []);

        $this->assertEquals('TEXT', $parser->getType());
    }

    public function testFactoryDefaultsToTextForSummary(): void
    {
        $parser = $this->factory->getParserForProperty('SUMMARY', []);

        $this->assertEquals('TEXT', $parser->getType());
    }

    public function testFactoryDefaultsToDateTimeForDtStart(): void
    {
        $parser = $this->factory->getParserForProperty('DTSTART', []);

        $this->assertEquals('DATE-TIME', $parser->getType());
    }

    public function testFactoryDefaultsToDurationForDuration(): void
    {
        $parser = $this->factory->getParserForProperty('DURATION', []);

        $this->assertEquals('DURATION', $parser->getType());
    }

    public function testFactoryDefaultsToIntegerForSequence(): void
    {
        $parser = $this->factory->getParserForProperty('SEQUENCE', []);

        $this->assertEquals('INTEGER', $parser->getType());
    }

    public function testFactoryDefaultsToBooleanForRsvp(): void
    {
        $parser = $this->factory->getParserForProperty('RSVP', []);

        $this->assertEquals('BOOLEAN', $parser->getType());
    }

    public function testFactoryDefaultsToUriForUrl(): void
    {
        $parser = $this->factory->getParserForProperty('URL', []);

        $this->assertEquals('URI', $parser->getType());
    }

    public function testFactoryThrowsForUnknownType(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Unknown data type");

        $this->factory->getParser('UNKNOWN-TYPE');
    }

    public function testFactoryCachesParsers(): void
    {
        $parser1 = $this->factory->getParser('DATE');
        $parser2 = $this->factory->getParser('DATE');

        $this->assertSame($parser1, $parser2);
    }

    public function testFactoryHasParserReturnsTrueForSupportedType(): void
    {
        $this->assertTrue($this->factory->hasParser('DATE'));
        $this->assertTrue($this->factory->hasParser('DATE-TIME'));
        $this->assertTrue($this->factory->hasParser('TEXT'));
    }

    public function testFactoryHasParserReturnsFalseForUnknownType(): void
    {
        $this->assertFalse($this->factory->hasParser('UNKNOWN'));
    }

    public function testFactoryHasParserIsCaseInsensitive(): void
    {
        $this->assertTrue($this->factory->hasParser('date'));
        $this->assertTrue($this->factory->hasParser('Date'));
        $this->assertTrue($this->factory->hasParser('DATE'));
    }

    public function testFactoryGetSupportedTypes(): void
    {
        $types = $this->factory->getSupportedTypes();

        $this->assertIsArray($types);
        $this->assertContains('DATE', $types);
        $this->assertContains('DATE-TIME', $types);
        $this->assertContains('TEXT', $types);
        $this->assertContains('INTEGER', $types);
        $this->assertContains('DURATION', $types);
        $this->assertContains('BOOLEAN', $types);
        $this->assertContains('URI', $types);
        $this->assertContains('BINARY', $types);
        $this->assertContains('FLOAT', $types);
        $this->assertContains('CAL-ADDRESS', $types);
        $this->assertContains('PERIOD', $types);
        $this->assertContains('TIME', $types);
        $this->assertContains('UTC-OFFSET', $types);
        $this->assertContains('RECUR', $types);
    }

    public function testFactoryParseValue(): void
    {
        // For now, parsers return the value as-is (placeholder implementation)
        $result = $this->factory->parseValue('SUMMARY', 'Test Meeting', []);

        $this->assertEquals('Test Meeting', $result);
    }

    public function testFactoryClearCache(): void
    {
        $parser1 = $this->factory->getParser('DATE');
        $this->factory->clearCache();
        $parser2 = $this->factory->getParser('DATE');

        $this->assertNotSame($parser1, $parser2);
    }

    public function testFactoryRegisterCustomParser(): void
    {
        $customParser = new class implements ValueParserInterface {
            public function parse(string $value, array $parameters = []): mixed
            {
                return 'custom:' . $value;
            }

            public function getType(): string
            {
                return 'CUSTOM';
            }

            public function canParse(string $value): bool
            {
                return true;
            }
        };

        $this->factory->registerParser('CUSTOM', $customParser);

        $parser = $this->factory->getParser('CUSTOM');
        $this->assertSame($customParser, $parser);
        $this->assertEquals('CUSTOM', $parser->getType());
    }

    public function testFactoryPropertyDefaultsAreCaseInsensitive(): void
    {
        $parser1 = $this->factory->getParserForProperty('summary', []);
        $parser2 = $this->factory->getParserForProperty('SUMMARY', []);

        $this->assertEquals('TEXT', $parser1->getType());
        $this->assertEquals('TEXT', $parser2->getType());
    }

    public function testFactoryAllDataTypesAreSupported(): void
    {
        $types = [
            'BINARY', 'BOOLEAN', 'CAL-ADDRESS', 'DATE', 'DATE-TIME',
            'DURATION', 'FLOAT', 'INTEGER', 'PERIOD', 'RECUR',
            'TEXT', 'TIME', 'URI', 'UTC-OFFSET'
        ];

        foreach ($types as $type) {
            $this->assertTrue(
                $this->factory->hasParser($type),
                "Factory should support type: {$type}"
            );

            $parser = $this->factory->getParser($type);
            $this->assertInstanceOf(
                ValueParserInterface::class,
                $parser,
                "Parser for {$type} should implement ValueParserInterface"
            );
        }
    }

    public function testFactoryValueParameterOverridesPropertyDefault(): void
    {
        // DTSTART defaults to DATE-TIME
        $parser = $this->factory->getParserForProperty('DTSTART', []);
        $this->assertEquals('DATE-TIME', $parser->getType());

        // But VALUE=DATE overrides
        $parser = $this->factory->getParserForProperty('DTSTART', ['VALUE' => 'DATE']);
        $this->assertEquals('DATE', $parser->getType());
    }
}
