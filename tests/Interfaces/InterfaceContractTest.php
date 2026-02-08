<?php

declare(strict_types=1);

namespace Icalendar\Tests\Interfaces;

use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VCalendar;
use Icalendar\Parser\Parser;
use Icalendar\Parser\ParserInterface;
use Icalendar\Property\PropertyInterface;
use Icalendar\Validation\ErrorSeverity;
use Icalendar\Validation\ValidationError;
use Icalendar\Value\ValueInterface;
use Icalendar\Writer\Writer;
use Icalendar\Writer\WriterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for core interface contracts
 */
class InterfaceContractTest extends TestCase
{
    public function testParserInterfaceContract(): void
    {
        $parser = new Parser();

        $this->assertInstanceOf(ParserInterface::class, $parser);

        // Test setStrict returns void
        $parser->setStrict(true);

        // Test getErrors returns array
        $errors = $parser->getErrors();
        $this->assertIsArray($errors);

        // Test that errors are ValidationError instances (when populated)
        foreach ($errors as $error) {
            $this->assertInstanceOf(ValidationError::class, $error);
        }
    }

    public function testWriterInterfaceContract(): void
    {
        $writer = new Writer();

        $this->assertInstanceOf(WriterInterface::class, $writer);

        // Test setLineFolding returns void
        $writer->setLineFolding(true, 75);

        // Test write returns string
        $calendar = new VCalendar();
        $output = $writer->write($calendar);
        $this->assertIsString($output);
    }

    public function testComponentInterfaceContract(): void
    {
        $calendar = new VCalendar();

        $this->assertInstanceOf(ComponentInterface::class, $calendar);

        // Test getName returns string
        $this->assertIsString($calendar->getName());
        $this->assertEquals('VCALENDAR', $calendar->getName());

        // Test getProperties returns array
        $properties = $calendar->getProperties();
        $this->assertIsArray($properties);

        // Test getComponents returns array
        $components = $calendar->getComponents();
        $this->assertIsArray($components);

        // Test getComponents with type filter returns array
        $events = $calendar->getComponents('VEVENT');
        $this->assertIsArray($events);

        // Test getParent returns null or ComponentInterface
        $parent = $calendar->getParent();
        $this->assertNull($parent);
    }

    public function testPropertyInterfaceMethodsExist(): void
    {
        // Create a mock property to test interface contract
        $mockProperty = new class implements PropertyInterface {
            #[\Override]
            public function getName(): string
            {
                return 'TEST';
            }

            #[\Override]
            public function getValue(): ValueInterface
            {
                throw new \RuntimeException('Not implemented');
            }

            #[\Override]
            public function setValue(ValueInterface $value): void
            {
            }

            #[\Override]
            public function getParameter(string $name): ?string
            {
                return null;
            }

            #[\Override]
            public function getParameters(): array
            {
                return [];
            }

            #[\Override]
            public function setParameter(string $name, string $value): void
            {
            }

            #[\Override]
            public function removeParameter(string $name): void
            {
            }
        };

        $this->assertInstanceOf(PropertyInterface::class, $mockProperty);

        // Test return types
        $this->assertIsString($mockProperty->getName());
        $this->assertIsArray($mockProperty->getParameters());
        $this->assertNull($mockProperty->getParameter('NONEXISTENT'));
    }

    public function testValueInterfaceMethodsExist(): void
    {
        // Create a mock value to test interface contract
        $mockValue = new class implements ValueInterface {
            #[\Override]
            public function getType(): string
            {
                return 'TEXT';
            }

            #[\Override]
            public function getRawValue(): string
            {
                return 'test value';
            }

            #[\Override]
            public function serialize(): string
            {
                return 'test value';
            }

            #[\Override]
            public function isDefault(): bool
            {
                return true;
            }
        };

        $this->assertInstanceOf(ValueInterface::class, $mockValue);

        // Test return types
        $this->assertIsString($mockValue->getType());
        $this->assertIsString($mockValue->serialize());
        $this->assertIsBool($mockValue->isDefault());
    }

    public function testErrorSeverityEnum(): void
    {
        // Test enum cases exist
        $this->assertInstanceOf(ErrorSeverity::class, ErrorSeverity::WARNING);
        $this->assertInstanceOf(ErrorSeverity::class, ErrorSeverity::ERROR);
        $this->assertInstanceOf(ErrorSeverity::class, ErrorSeverity::FATAL);

        // Test enum values
        $this->assertEquals('WARNING', ErrorSeverity::WARNING->value);
        $this->assertEquals('ERROR', ErrorSeverity::ERROR->value);
        $this->assertEquals('FATAL', ErrorSeverity::FATAL->value);

        // Test enum can be used in ValidationError
        $error = new ValidationError(
            'TEST-001',
            'Test message',
            'VCALENDAR',
            null,
            null,
            1,
            ErrorSeverity::WARNING
        );

        $this->assertEquals(ErrorSeverity::WARNING, $error->severity);
    }

    public function testParserInterfaceHasCompletePhpDoc(): void
    {
        $reflection = new \ReflectionClass(ParserInterface::class);

        // Check class has PHPDoc
        $this->assertNotEmpty($reflection->getDocComment());

        // Check each method has PHPDoc with proper tags
        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotEmpty($docComment, "Method {$method->getName()} should have PHPDoc");

            // Check for type hints on parameters and return types
            $this->assertTrue($method->hasReturnType(), "Method {$method->getName()} should have return type");
        }
    }

    public function testWriterInterfaceHasCompletePhpDoc(): void
    {
        $reflection = new \ReflectionClass(WriterInterface::class);

        // Check class has PHPDoc
        $this->assertNotEmpty($reflection->getDocComment());

        // Check each method has return type
        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotEmpty($docComment, "Method {$method->getName()} should have PHPDoc");
            $this->assertTrue($method->hasReturnType(), "Method {$method->getName()} should have return type");
        }
    }

    public function testComponentInterfaceHasCompletePhpDoc(): void
    {
        $reflection = new \ReflectionClass(ComponentInterface::class);

        // Check class has PHPDoc
        $this->assertNotEmpty($reflection->getDocComment());

        // Check each method has return type
        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotEmpty($docComment, "Method {$method->getName()} should have PHPDoc");
            $this->assertTrue($method->hasReturnType(), "Method {$method->getName()} should have return type");
        }
    }

    public function testPropertyInterfaceHasCompletePhpDoc(): void
    {
        $reflection = new \ReflectionClass(PropertyInterface::class);

        // Check class has PHPDoc
        $this->assertNotEmpty($reflection->getDocComment());

        // Check each method has return type
        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotEmpty($docComment, "Method {$method->getName()} should have PHPDoc");
            $this->assertTrue($method->hasReturnType(), "Method {$method->getName()} should have return type");
        }
    }

    public function testValueInterfaceHasCompletePhpDoc(): void
    {
        $reflection = new \ReflectionClass(ValueInterface::class);

        // Check class has PHPDoc
        $this->assertNotEmpty($reflection->getDocComment());

        // Check each method has return type
        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotEmpty($docComment, "Method {$method->getName()} should have PHPDoc");
            $this->assertTrue($method->hasReturnType(), "Method {$method->getName()} should have return type");
        }
    }

    public function testErrorSeverityEnumHasCompletePhpDoc(): void
    {
        $reflection = new \ReflectionClass(ErrorSeverity::class);

        // Check class has PHPDoc
        $this->assertNotEmpty($reflection->getDocComment());
    }

    public function testParserInterfaceMatchesPrd(): void
    {
        $reflection = new \ReflectionClass(ParserInterface::class);

        // Check all required methods exist
        $methods = array_map(fn($m) => $m->getName(), $reflection->getMethods());
        $this->assertContains('parse', $methods);
        $this->assertContains('parseFile', $methods);
        $this->assertContains('setStrict', $methods);
        $this->assertContains('getErrors', $methods);

        // Check method signatures
        $parseMethod = $reflection->getMethod('parse');
        $this->assertEquals(1, $parseMethod->getNumberOfParameters());
        $this->assertEquals('data', $parseMethod->getParameters()[0]->getName());

        $parseFileMethod = $reflection->getMethod('parseFile');
        $this->assertEquals(1, $parseFileMethod->getNumberOfParameters());
        $this->assertEquals('filepath', $parseFileMethod->getParameters()[0]->getName());

        $setStrictMethod = $reflection->getMethod('setStrict');
        $this->assertEquals(1, $setStrictMethod->getNumberOfParameters());
        $this->assertEquals('strict', $setStrictMethod->getParameters()[0]->getName());
    }

    public function testWriterInterfaceMatchesPrd(): void
    {
        $reflection = new \ReflectionClass(WriterInterface::class);

        // Check all required methods exist
        $methods = array_map(fn($m) => $m->getName(), $reflection->getMethods());
        $this->assertContains('write', $methods);
        $this->assertContains('writeToFile', $methods);
        $this->assertContains('setLineFolding', $methods);

        // Check method signatures
        $writeMethod = $reflection->getMethod('write');
        $this->assertEquals(1, $writeMethod->getNumberOfParameters());
        $this->assertEquals('calendar', $writeMethod->getParameters()[0]->getName());

        $writeToFileMethod = $reflection->getMethod('writeToFile');
        $this->assertEquals(2, $writeToFileMethod->getNumberOfParameters());

        $setLineFoldingMethod = $reflection->getMethod('setLineFolding');
        $this->assertEquals(2, $setLineFoldingMethod->getNumberOfParameters());
        $this->assertTrue($setLineFoldingMethod->getParameters()[1]->isOptional());
        $this->assertEquals(75, $setLineFoldingMethod->getParameters()[1]->getDefaultValue());
    }
}
