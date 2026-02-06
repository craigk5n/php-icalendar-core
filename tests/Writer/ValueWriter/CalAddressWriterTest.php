<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Writer\ValueWriter\CalAddressWriter;
use PHPUnit\Framework\TestCase;

class CalAddressWriterTest extends TestCase
{
    private CalAddressWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new CalAddressWriter();
    }

    // ========== write Tests ==========

    public function testWriteSimpleEmail(): void
    {
        $input = 'john.doe@example.com';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:john.doe@example.com', $result);
    }

    public function testWriteAlreadyWithMailto(): void
    {
        $input = 'mailto:jane.smith@company.com';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:jane.smith@company.com', $result);
    }

    public function testWriteWithEmailWithName(): void
    {
        $input = 'John Doe <john.doe@example.com>';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:John Doe <john.doe@example.com>', $result);
    }

    public function testWriteEmptyString(): void
    {
        $input = '';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:', $result);
    }

    public function testWriteWithQueryString(): void
    {
        $input = 'test@example.com?subject=Meeting&body=Please attend';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:test@example.com?subject=Meeting&body=Please attend', $result);
    }

    public function testWriteComplexEmail(): void
    {
        $input = 'user+tag@example.co.uk';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:user+tag@example.co.uk', $result);
    }

    public function testWriteWithSpecialCharacters(): void
    {
        $input = 'test_user@example-domain.com';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:test_user@example-domain.com', $result);
    }

    public function testWriteWithNumbers(): void
    {
        $input = 'user123@example456.com';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:user123@example456.com', $result);
    }

    public function testWriteCaseInsensitiveMailto(): void
    {
        $input = 'MAILTO:test@example.com';
        $result = $this->writer->write($input);
        
        // Should not double-add mailto since it starts with MAILTO
        $this->assertEquals('MAILTO:test@example.com', $result);
    }

    public function testWriteWithMixedCaseMailto(): void
    {
        $input = 'MailTo:test@example.com';
        $result = $this->writer->write($input);
        
        // Should not double-add mailto since it starts with MailTo
        $this->assertEquals('MailTo:test@example.com', $result);
    }

    public function testWriteWithLeadingWhitespace(): void
    {
        $input = '  test@example.com';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:  test@example.com', $result);
    }

    public function testWriteWithTrailingWhitespace(): void
    {
        $input = 'test@example.com  ';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:test@example.com  ', $result);
    }

    public function testWriteUnicodeEmail(): void
    {
        $input = '用户@例子.测试';
        $result = $this->writer->write($input);
        
        $this->assertEquals('mailto:用户@例子.测试', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CalAddressWriter expects string, got integer');
        
        $this->writer->write(123);
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CalAddressWriter expects string, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CalAddressWriter expects string, got array');
        
        $this->writer->write(['test@example.com']);
    }

    public function testWriteObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CalAddressWriter expects string, got object');
        
        $this->writer->write(new \stdClass());
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('CAL-ADDRESS', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $this->assertTrue($this->writer->canWrite('test@example.com'));
        $this->assertTrue($this->writer->canWrite('mailto:test@example.com'));
        $this->assertTrue($this->writer->canWrite(''));
        $this->assertTrue($this->writer->canWrite('simple text'));
        
        $this->assertFalse($this->writer->canWrite(123));
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
        $this->assertFalse($this->writer->canWrite(true));
        $this->assertFalse($this->writer->canWrite(12.34));
    }

    // ========== Integration Tests ==========

    public function testWriteAlwaysStartsWithMailto(): void
    {
        $testInputs = [
            'simple@example.com',
            'mailto:already@example.com',
            'MAILTO:uppercase@example.com',
            'MailTo:mixedcase@example.com',
            '',
            'text',
            'user+tag@example.com',
        ];
        
        foreach ($testInputs as $input) {
            $result = $this->writer->write($input);
            
            // All results should start with mailto: (preserve case variations)
            $this->assertStringStartsWith('mailto:', strtolower(substr($result, 0, 7)), 
                "Result should start with mailto: for input: $input");
        }
    }

    public function testWriteDoesntDoublePrefixMailto(): void
    {
        $testInputs = [
            'mailto:test@example.com',
            'MAILTO:test@example.com',
            'MailTo:test@example.com',
            'mailto:test@example.com', // Plain mailto stays the same
            'user@example.com', // No prefix - should get mailto:
        ];
        
        foreach ($testInputs as $input) {
            $result = $this->writer->write($input);
            
            // Should not have double mailto:
            $this->assertStringNotContainsString('mailto:mailto:', strtolower($result));
            // Should always have lowercase mailto: prefix
            $this->assertStringStartsWith('mailto:', strtolower($result));
        }
    }

    public function testWriteRoundTrip(): void
    {
        $emails = [
            'simple@example.com',
            'user.name@domain.com',
            'user+tag@example.co.uk',
            'test_user@example-domain.com',
            '123@example456.com',
        ];
        
        foreach ($emails as $email) {
            $result = $this->writer->write($email);
            
            // If we write it again, it shouldn't change
            $secondResult = $this->writer->write($result);
            $this->assertEquals($result, $secondResult);
        }
    }

    public function testWriteEdgeCases(): void
    {
        // Test with various prefixes that look like mailto but aren't exactly
        $edgeCases = [
            ' mailto:test@example.com', // Leading space
            'mailto :test@example.com', // Space after mailto
            'mailt:test@example.com', // Missing 'o'
            'mailtoo:test@example.com', // Extra 'o'
        ];
        
        foreach ($edgeCases as $input) {
            $result = $this->writer->write($input);
            
            // All should get mailto: prefixed since they don't start with exactly 'mailto:'
            if (!str_starts_with($input, 'mailto:')) {
                $this->assertEquals('mailto:' . $input, $result);
            }
        }
    }

    public function testWriteRealWorldExamples(): void
    {
        $realWorldEmails = [
            'organizer@example.com',
            'attendee1@company.org',
            'meeting.room@conference.venue.com',
            'user.name+calendar@sub.domain.co.uk',
        ];
        
        foreach ($realWorldEmails as $email) {
            $result = $this->writer->write($email);
            
            $this->assertStringStartsWith('mailto:', $result);
            $this->assertStringContainsString($email, $result);
        }
    }
}