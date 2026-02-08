<?php

declare(strict_types=1);

namespace Icalendar\Tests\Validation;

use Icalendar\Exception\ParseException;
use Icalendar\Validation\SecurityValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SecurityValidator
 * 
 * Covers:
 * - NFR-010: XXE Prevention in ATTACH properties
 * - NFR-011: Recursion depth limiting
 * - NFR-012: SSRF Prevention via URI validation
 * - NFR-013: Text sanitization
 */
class SecurityValidatorTest extends TestCase
{
    private SecurityValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = new SecurityValidator();
    }

    /** @test */
    public function testSecurityDepthLimit(): void
    {
        $validator = new SecurityValidator(maxDepth: 10);
        
        // Should not throw at depth 10
        $validator->checkDepth(10);
        $this->assertTrue(true);
    }

    /** @test */
    public function testSecurityDepthExceeded(): void
    {
        $validator = new SecurityValidator(maxDepth: 5);
        
        $this->expectException(ParseException::class);
        $this->expectExceptionCode(0);
        
        try {
            $validator->checkDepth(6);
        } catch (ParseException $e) {
            $this->assertSame(ParseException::ERR_SECURITY_DEPTH_EXCEEDED, $e->getErrorCode());
            throw $e;
        }
    }

    /** @test */
    public function testSecurityXxeBlocked(): void
    {
        // Test XXE detection in file content
        $filePath = sys_get_temp_dir() . '/test_xxe_' . uniqid() . '.ics';
        file_put_contents($filePath, "BEGIN:VCALENDAR\n<!ENTITY xxe SYSTEM \"file:///etc/passwd\">\nEND:VCALENDAR");
        
        $parser = new \Icalendar\Parser\Parser();
        
        $this->expectException(ParseException::class);
        
        try {
            $parser->parseFile($filePath);
        } catch (ParseException $e) {
            unlink($filePath);
            $this->assertSame(ParseException::ERR_SECURITY_XXE_ATTEMPT, $e->getErrorCode());
            throw $e;
        }
    }

    /** @test */
    public function testSecuritySsrfPrivateIpBlocked(): void
    {
        // Test private IP blocking (SSRF protection)
        $privateIps = [
            'http://127.0.0.1/test',
            'http://10.0.0.1/test',
            'http://192.168.1.1/test',
            'http://172.16.0.1/test',
            'http://localhost/test',
        ];

        foreach ($privateIps as $uri) {
            try {
                $this->validator->validateUri($uri);
                $this->fail("Should have thrown exception for private IP: {$uri}");
            } catch (ParseException $e) {
                $this->assertSame(
                    ParseException::ERR_SECURITY_PRIVATE_IP, 
                    $e->getErrorCode(),
                    "Wrong error code for URI: {$uri}"
                );
                $this->assertStringContainsString('private IP', $e->getMessage());
            }
        }
    }

    /** @test */
    public function testSecuritySsrfFileSchemeBlocked(): void
    {
        // Test file:// scheme blocking
        $this->expectException(ParseException::class);
        
        try {
            $this->validator->validateUri('file:///etc/passwd');
        } catch (ParseException $e) {
            $this->assertSame(ParseException::ERR_SECURITY_INVALID_SCHEME, $e->getErrorCode());
            throw $e;
        }
    }

    /** @test */
    public function testSecurityTextSanitization(): void
    {
        $input = "Hello\x00World\x01\x02\x03"; // Null byte + control chars
        $expected = 'HelloWorld\x01\x02\x03'; // Null removed, control chars escaped
        
        $result = $this->validator->sanitizeText($input);
        $this->assertSame($expected, $result);
    }

    /** @test */
    public function testSecurityNullByteStripped(): void
    {
        $input = "Test\x00String\x00With\x00Nulls";
        $expected = 'TestStringWithNulls';
        
        $result = $this->validator->sanitizeText($input);
        $this->assertSame($expected, $result);
        $this->assertStringNotContainsString("\x00", $result);
    }

    /** @test */
    public function testSecurityAllowedSchemes(): void
    {
        // These should all pass
        $allowedUris = [
            'https://example.com/file.ics',
            'http://example.com/test',
            'mailto:test@example.com',
            'tel:+1234567890',
            'urn:uuid:12345678-1234-1234-1234-123456789012',
        ];

        foreach ($allowedUris as $uri) {
            // Should not throw
            $this->validator->validateUri($uri);
        }
        
        $this->assertTrue(true); // If we get here, all URIs passed
    }

    /** @test */
    public function testSecurityDataUriSizeLimit(): void
    {
        // Create a data URI that exceeds 1MB
        $largeData = str_repeat('A', 2000000); // ~2MB
        $dataUri = 'data:text/plain;base64,' . base64_encode($largeData);
        
        $this->expectException(ParseException::class);
        
        try {
            $this->validator->validateUri($dataUri);
        } catch (ParseException $e) {
            $this->assertSame(ParseException::ERR_SECURITY_DATA_URI_TOO_LARGE, $e->getErrorCode());
            throw $e;
        }
    }

    /** @test */
    public function testSecurityDataUriAllowed(): void
    {
        // Small data URI should be allowed
        $smallData = base64_encode('Hello, World!');
        $dataUri = 'data:text/plain;base64,' . $smallData;
        
        $this->validator->validateUri($dataUri);
        $this->assertTrue(true); // Passed validation
    }

    /** @test */
    public function testSecurityUriSchemeCaseInsensitive(): void
    {
        // Should block file:// regardless of case
        $this->expectException(ParseException::class);
        $this->validator->validateUri('FILE:///etc/passwd');
    }

    /** @test */
    public function testSecurityCustomAllowedSchemes(): void
    {
        $validator = new SecurityValidator(allowedSchemes: ['ftp', 'sftp']);
        
        // Should allow ftp
        $validator->validateUri('ftp://example.com/file');
        
        // Should block http
        $this->expectException(ParseException::class);
        $validator->validateUri('http://example.com');
    }

    /** @test */
    public function testSecurityMaxDepthConfigurable(): void
    {
        $validator = new SecurityValidator(maxDepth: 50);
        $this->assertSame(50, $validator->getMaxDepth());
        
        $validator->setMaxDepth(100);
        $this->assertSame(100, $validator->getMaxDepth());
    }

    /** @test */
    public function testSecurityDataUriSizeConfigurable(): void
    {
        $validator = new SecurityValidator(maxDataUriSize: 100);
        
        $smallData = base64_encode('tiny');
        $dataUri = 'data:text/plain;base64,' . $smallData;
        $validator->validateUri($dataUri); // Should pass
        
        // Larger data should fail
        $largeData = str_repeat('A', 200);
        $this->expectException(ParseException::class);
        $validator->validateUri('data:text/plain;base64,' . base64_encode($largeData));
    }

    /** @test */
    public function testSecurityControlCharsEscaped(): void
    {
        $input = "Tab:\t Newline:\n Carriage:\r Bell:\x07";
        $expected = "Tab:\t Newline:\n Carriage:\r Bell:\\x07"; // Bell escaped as \x07
        
        $result = $this->validator->sanitizeText($input);
        $this->assertSame($expected, $result);
    }

    /** @test */
    public function testSecurityMaxDepthConstructor(): void
    {
        $validator = new SecurityValidator(maxDepth: 200);
        $this->assertSame(200, $validator->getMaxDepth());
    }

    /** @test */
    public function testSecuritySetAllowedSchemes(): void
    {
        $validator = new SecurityValidator();
        
        // Initially has default schemes
        $this->assertContains('http', $validator->getAllowedSchemes());
        
        // Set custom schemes
        $validator->setAllowedSchemes(['custom']);
        $this->assertSame(['custom'], $validator->getAllowedSchemes());
        
        // Should now allow custom scheme
        $validator->validateUri('custom://test');
        
        // Should block http
        $this->expectException(ParseException::class);
        $validator->validateUri('http://example.com');
    }
}