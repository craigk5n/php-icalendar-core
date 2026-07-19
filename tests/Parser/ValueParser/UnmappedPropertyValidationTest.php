<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Parser\ValueParser\ValueParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A property absent from the parser's property/type map silently inherits the
 * TEXT default, and TextParser only unescapes and cannot fail -- so an absent
 * property is an unvalidated one.
 *
 * TZURL and SOURCE are URIs (RFC 5545 §3.8.3.5, RFC 7986 §5.8) and were
 * accepting anything at all. NAME and RELATED-TO happen to be TEXT, so they
 * landed on the right parser by accident; mapping them makes that deliberate
 * rather than coincidental, and stops a future reader assuming they were
 * considered and excluded.
 */
class UnmappedPropertyValidationTest extends TestCase
{
    private ValueParserFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new ValueParserFactory();
    }

    /** @return array<string, array{string, string}> */
    public static function propertyTypeProvider(): array
    {
        return [
            'TZURL is a URI' => ['TZURL', 'URI'],
            'SOURCE is a URI' => ['SOURCE', 'URI'],
            'NAME is TEXT' => ['NAME', 'TEXT'],
            'RELATED-TO is TEXT' => ['RELATED-TO', 'TEXT'],
            'REQUEST-STATUS is structured' => ['REQUEST-STATUS', 'REQUEST-STATUS'],
        ];
    }

    #[DataProvider('propertyTypeProvider')]
    public function testPropertyResolvesToItsDeclaredType(string $property, string $expectedType): void
    {
        self::assertSame(
            $expectedType,
            $this->factory->getParserForProperty($property)->getType(),
            "{$property} must resolve to {$expectedType}, not fall through to the TEXT default"
        );
    }

    /**
     * The point of mapping the URI ones: a malformed value is now reportable
     * rather than silently accepted.
     *
     * @return array<string, array{string}>
     */
    public static function uriPropertyProvider(): array
    {
        return [
            'TZURL' => ['TZURL'],
            'SOURCE' => ['SOURCE'],
        ];
    }

    /**
     * The payoff of the mapping. UriParser enforces the scheme grammar only in
     * strict mode -- lenient URI handling is deliberately permissive, since
     * RFC 3986 admits far more shapes than a simple pattern captures. What
     * changed is that strict mode can now reject a malformed TZURL at all:
     * while the property fell through to TEXT, no mode could, because
     * TextParser cannot fail.
     */
    #[DataProvider('uriPropertyProvider')]
    public function testMalformedUriIsRejectedInStrictMode(string $property): void
    {
        $factory = new ValueParserFactory();
        $factory->setStrict(true);
        $parser = $factory->getParserForProperty($property);

        self::assertFalse(
            $parser->canParse('not a uri at all'),
            "{$property} must not accept arbitrary text in strict mode"
        );
    }

    #[DataProvider('uriPropertyProvider')]
    public function testWellFormedUriIsAcceptedInEitherMode(string $property): void
    {
        $strict = new ValueParserFactory();
        $strict->setStrict(true);

        self::assertTrue($strict->getParserForProperty($property)->canParse('http://tz.example.com/zones/ny.ics'));
        self::assertTrue($this->factory->getParserForProperty($property)->canParse('http://tz.example.com/zones/ny.ics'));
    }
}
