<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Writer\ValueWriter\UriWriter;
use PHPUnit\Framework\TestCase;

class UriWriterTest extends TestCase
{
    private UriWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new UriWriter();
    }

    // ========== write Tests ==========

    public function testWriteHttpUrl(): void
    {
        $input = 'http://example.com';
        $result = $this->writer->write($input);
        
        $this->assertEquals('http://example.com', $result);
    }

    public function testWriteHttpsUrl(): void
    {
        $input = 'https://secure.example.com/path?query=value';
        $result = $this->writer->write($input);
        
        $this->assertEquals('https://secure.example.com/path?query=value', $result);
    }

    public function testWriteFtpUrl(): void
    {
        $input = 'ftp://files.example.com/documents';
        $result = $this->writer->write($input);
        
        $this->assertEquals('ftp://files.example.com/documents', $result);
    }

    public function testWriteMailtoUrl(): void
    {
        $input = 'mailto:user@example.com?subject=Hello';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:user@example.com?subject=Hello', $result);
    }

    public function testWriteEmptyString(): void
    {
        $input = '';
        $result = $this->writer->write($input);
        
        $this->assertEquals('', $result);
    }

    public function testWriteSimpleString(): void
    {
        $input = 'simple text';
        $result = $this->writer->write($input);
        
        $this->assertEquals('simple text', $result);
    }

    public function testWriteUrlWithSpecialCharacters(): void
    {
        $input = 'https://example.com/path with spaces/file-name.txt?param=value&other=test';
        $result = $this->writer->write($input);
        
        $this->assertEquals('https://example.com/path with spaces/file-name.txt?param=value&other=test', $result);
    }

    public function testWriteUnicodeUrl(): void
    {
        $input = 'https://例子.测试/路径';
        $result = $this->writer->write($input);
        
        $this->assertEquals('https://例子.测试/路径', $result);
    }

    public function testWriteWithPort(): void
    {
        $input = 'http://localhost:8080/api';
        $result = $this->writer->write($input);
        
        $this->assertEquals('http://localhost:8080/api', $result);
    }

    public function testWriteWithAuth(): void
    {
        $input = 'http://user:pass@example.com';
        $result = $this->writer->write($input);
        
        $this->assertEquals('http://user:pass@example.com', $result);
    }

    public function testWriteWithFragment(): void
    {
        $input = 'https://example.com/page.html#section';
        $result = $this->writer->write($input);
        
        $this->assertEquals('https://example.com/page.html#section', $result);
    }

    public function testWriteDataUri(): void
    {
        $input = 'data:text/plain;base64,SGVsbG8gV29ybGQ=';
        $result = $this->writer->write($input);
        
        $this->assertEquals('data:text/plain;base64,SGVsbG8gV29ybGQ=', $result);
    }

    public function testWriteFileUri(): void
    {
        $input = 'file:///path/to/file.txt';
        $result = $this->writer->write($input);
        
        $this->assertEquals('file:///path/to/file.txt', $result);
    }

    public function testWriteCustomScheme(): void
    {
        $input = 'custom://resource.identifier';
        $result = $this->writer->write($input);
        
        $this->assertEquals('custom://resource.identifier', $result);
    }

    public function testWriteRelativeUrl(): void
    {
        $input = '/relative/path/to/resource';
        $result = $this->writer->write($input);
        
        $this->assertEquals('/relative/path/to/resource', $result);
    }

    public function testWriteUrlWithQueryParameters(): void
    {
        $input = 'https://api.example.com/search?q=icalendar&format=json&limit=10';
        $result = $this->writer->write($input);
        
        $this->assertEquals('https://api.example.com/search?q=icalendar&format=json&limit=10', $result);
    }

    public function testWriteWithEscapedCharacters(): void
    {
        $input = 'https://example.com/path%20with%20spaces/document.pdf';
        $result = $this->writer->write($input);
        
        $this->assertEquals('https://example.com/path%20with%20spaces/document.pdf', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UriWriter expects string, got integer');
        
        $this->writer->write(123);
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UriWriter expects string, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UriWriter expects string, got array');
        
        $this->writer->write(['url' => 'https://example.com']);
    }

    public function testWriteObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UriWriter expects string, got object');
        
        $this->writer->write(new \stdClass());
    }

    public function testWriteBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UriWriter expects string, got boolean');
        
        $this->writer->write(true);
    }

    public function testWriteFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UriWriter expects string, got double');
        
        $this->writer->write(3.14);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('URI', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $this->assertTrue($this->writer->canWrite('https://example.com'));
        $this->assertTrue($this->writer->canWrite('mailto:test@example.com'));
        $this->assertTrue($this->writer->canWrite(''));
        $this->assertTrue($this->writer->canWrite('simple string'));
        $this->assertTrue($this->writer->canWrite('相对路径'));
        
        $this->assertFalse($this->writer->canWrite(123));
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
        $this->assertFalse($this->writer->canWrite(true));
        $this->assertFalse($this->writer->canWrite(3.14));
    }

    // ========== Integration Tests ==========

    public function testWritePassthrough(): void
    {
        // UriWriter should be a simple passthrough - no modification of input
        $testCases = [
            'https://example.com',
            'http://localhost:8080',
            'ftp://files.example.com',
            'mailto:user@example.com',
            'data:text/plain,Hello',
            'file:///path/to/file',
            'custom-scheme:resource',
            '/relative/path',
            'simple text',
            '',
            'Special chars: !@#$%^&*()',
            'Unicode: 你好世界 🌍',
        ];
        
        foreach ($testCases as $input) {
            $result = $this->writer->write($input);
            $this->assertEquals($input, $result, "Should passthrough unchanged: $input");
        }
    }

    public function testWriteRealWorldExamples(): void
    {
        $realWorldUris = [
            'https://www.google.com/calendar',
            'https://outlook.office.com/calendar',
            'https://calendar.yahoo.com',
            'mailto:meeting@example.com',
            'https://api.example.com/events/12345',
            'https://example.com/calendar.ics',
            'webcal://example.com/calendar.ics',
            'file:///Users/username/Documents/calendar.ics',
        ];
        
        foreach ($realWorldUris as $uri) {
            $result = $this->writer->write($uri);
            $this->assertEquals($uri, $result);
        }
    }

    public function testWriteEdgeCases(): void
    {
        $edgeCases = [
            'https://example.com/', // Trailing slash
            'https://example.com//double//slashes', // Double slashes
            'https://example.com/path?query=', // Empty query value
            'https://example.com/path#', // Empty fragment
            'https://example.com:80/', // Default port
            'https://user@pass@example.com', // malformed auth
            '://missing-scheme.com', // Missing scheme
            'https://', // Incomplete URL
            'http://[2001:db8::1]/', // IPv6
        ];
        
        foreach ($edgeCases as $uri) {
            $result = $this->writer->write($uri);
            $this->assertEquals($uri, $result);
        }
    }

    public function testWriteVeryLongUri(): void
    {
        $longUri = 'https://example.com/' . str_repeat('path/', 100) . 'file.html?' . str_repeat('param=value&', 50) . 'final=true';
        $result = $this->writer->write($longUri);
        
        $this->assertEquals($longUri, $result);
        $this->assertGreaterThan(1000, strlen($result));
    }

    public function testWriteRoundTrip(): void
    {
        // Test that multiple writes produce the same result
        $testUris = [
            'https://example.com',
            'mailto:test@example.com',
            'file:///path/to/file.ics',
        ];
        
        foreach ($testUris as $uri) {
            $firstWrite = $this->writer->write($uri);
            $secondWrite = $this->writer->write($firstWrite);
            
            $this->assertEquals($uri, $firstWrite);
            $this->assertEquals($firstWrite, $secondWrite);
        }
    }

    public function testWriteWithWhitespace(): void
    {
        $whitespaceCases = [
            'https://example.com/path with spaces',
            'https://example.com/path\twith\ttabs',
            "https://example.com/path\nwith\nnewlines",
            ' https://example.com/leading-space',
            'https://example.com/trailing-space ',
        ];
        
        foreach ($whitespaceCases as $uri) {
            $result = $this->writer->write($uri);
            $this->assertEquals($uri, $result);
        }
    }

    public function testWriteStringNumbers(): void
    {
        // String numbers should be treated as strings, not converted
        $stringNumbers = [
            '123',
            '456.789',
            '0',
            '-123',
        ];
        
        foreach ($stringNumbers as $number) {
            $result = $this->writer->write($number);
            $this->assertEquals($number, $result);
            $this->assertIsString($result);
        }
    }
}