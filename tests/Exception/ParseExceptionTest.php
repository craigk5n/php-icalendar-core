<?php

declare(strict_types=1);

namespace Icalendar\Tests\Exception;

use Icalendar\Exception\ParseException;
use PHPUnit\Framework\TestCase;

class ParseExceptionTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $message = 'Test parse error';
        $errorCode = ParseException::ERR_INVALID_PROPERTY_FORMAT;
        $lineNumber = 42;
        $line = 'SUMMARY:Test value';
        $previous = new \RuntimeException('Previous error');

        $exception = new ParseException($message, $errorCode, $lineNumber, $line, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($errorCode, $exception->getErrorCode());
        $this->assertEquals($lineNumber, $exception->getContentLineNumber());
        $this->assertEquals($line, $exception->getContentLine());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $message = 'Test error';
        $errorCode = ParseException::ERR_INVALID_DATE;

        $exception = new ParseException($message, $errorCode);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($errorCode, $exception->getErrorCode());
        $this->assertEquals(0, $exception->getContentLineNumber());
        $this->assertNull($exception->getContentLine());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithLineNumberOnly(): void
    {
        $message = 'Test error';
        $errorCode = ParseException::ERR_INVALID_BINARY;
        $lineNumber = 15;

        $exception = new ParseException($message, $errorCode, $lineNumber);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($errorCode, $exception->getErrorCode());
        $this->assertEquals($lineNumber, $exception->getContentLineNumber());
        $this->assertNull($exception->getContentLine());
    }

    public function testGetErrorCode(): void
    {
        $exception = new ParseException('Test', ParseException::ERR_INVALID_BOOLEAN);
        $this->assertEquals(ParseException::ERR_INVALID_BOOLEAN, $exception->getErrorCode());
    }

    public function testGetContentLineNumber(): void
    {
        $exception = new ParseException('Test', ParseException::ERR_INVALID_CAL_ADDRESS, 100);
        $this->assertEquals(100, $exception->getContentLineNumber());
    }

    public function testGetContentLine(): void
    {
        $line = 'DTSTART:20260206T103000Z';
        $exception = new ParseException('Test', ParseException::ERR_INVALID_DATE_TIME, 5, $line);
        $this->assertEquals($line, $exception->getContentLine());
    }

    public function testGetContentLineReturnsNull(): void
    {
        $exception = new ParseException('Test', ParseException::ERR_INVALID_DURATION);
        $this->assertNull($exception->getContentLine());
    }

    public function testParserErrorCodes(): void
    {
        $codes = [
            ParseException::ERR_INVALID_LINE_ENDING,
            ParseException::ERR_MALFORMED_FOLDING,
            ParseException::ERR_LINE_TOO_LONG,
            ParseException::ERR_UTF8_SEQUENCE_BROKEN,
            ParseException::ERR_BINARY_CORRUPTION,
            ParseException::ERR_INVALID_PROPERTY_FORMAT,
            ParseException::ERR_INVALID_PROPERTY_NAME,
            ParseException::ERR_INVALID_PARAMETER_FORMAT,
            ParseException::ERR_UNCLOSED_QUOTED_STRING,
            ParseException::ERR_INVALID_RFC6868_ENCODING,
            ParseException::ERR_INVALID_MULTI_VALUE_PARAM,
            ParseException::ERR_TYPE_DECLARATION_MISMATCH,
        ];

        foreach ($codes as $code) {
            $exception = new ParseException('Test', $code);
            $this->assertEquals($code, $exception->getErrorCode());
        }
    }

    public function testDataTypeErrorCodes(): void
    {
        $codes = [
            ParseException::ERR_INVALID_BINARY,
            ParseException::ERR_INVALID_BOOLEAN,
            ParseException::ERR_INVALID_CAL_ADDRESS,
            ParseException::ERR_INVALID_DATE,
            ParseException::ERR_INVALID_DATE_TIME,
            ParseException::ERR_INVALID_DURATION,
            ParseException::ERR_INVALID_FLOAT,
            ParseException::ERR_INVALID_INTEGER,
            ParseException::ERR_INVALID_PERIOD,
            ParseException::ERR_INVALID_RECUR,
            ParseException::ERR_INVALID_TEXT,
            ParseException::ERR_INVALID_TIME,
            ParseException::ERR_INVALID_URI,
            ParseException::ERR_INVALID_UTC_OFFSET,
        ];

        foreach ($codes as $code) {
            $exception = new ParseException('Test', $code);
            $this->assertEquals($code, $exception->getErrorCode());
        }
    }

    public function testIOErrorCodes(): void
    {
        $codes = [
            ParseException::ERR_FILE_NOT_FOUND,
            ParseException::ERR_FILE_WRITE,
            ParseException::ERR_PERMISSION_DENIED,
        ];

        foreach ($codes as $code) {
            $exception = new ParseException('Test', $code);
            $this->assertEquals($code, $exception->getErrorCode());
        }
    }

    public function testSecurityErrorCodes(): void
    {
        $codes = [
            ParseException::ERR_SECURITY_DEPTH_EXCEEDED,
            ParseException::ERR_SECURITY_INVALID_SCHEME,
            ParseException::ERR_SECURITY_DATA_URI_TOO_LARGE,
            ParseException::ERR_SECURITY_PRIVATE_IP,
            ParseException::ERR_SECURITY_XXE_ATTEMPT,
        ];

        foreach ($codes as $code) {
            $exception = new ParseException('Test', $code);
            $this->assertEquals($code, $exception->getErrorCode());
        }
    }

    public function testRRuleErrorCodes(): void
    {
        $codes = [
            ParseException::ERR_RRULE_FREQ_REQUIRED,
            ParseException::ERR_RRULE_INVALID_INTERVAL,
            ParseException::ERR_RRULE_UNTIL_COUNT_EXCLUSIVE,
        ];

        foreach ($codes as $code) {
            $exception = new ParseException('Test', $code);
            $this->assertEquals($code, $exception->getErrorCode());
        }
    }

    public function testExceptionIsThrowable(): void
    {
        $exception = new ParseException('Test', ParseException::ERR_INVALID_FLOAT);
        
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionInheritance(): void
    {
        $exception = new ParseException('Test', ParseException::ERR_INVALID_INTEGER);
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}