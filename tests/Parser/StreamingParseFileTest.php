<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * parseFile() streams the file through Lexer::tokenizeFile() instead of reading
 * it whole.
 *
 * Lexer::tokenizeFile() was written for constant-memory tokenisation and had no
 * production caller: parseFile() ran file_get_contents() and handed the string
 * to the in-memory path, so every file parse cost memory proportional to the
 * file's size. On a 2.9 MB calendar the peak fell from 68.9 MB to 34.0 MB once
 * the streaming path was wired in, the remainder being the component graph
 * itself.
 *
 * The switch moves two things onto the production path that were previously
 * exercised only by LexerTest -- the chunked reader, and an XXE scan that can no
 * longer see the whole file at once -- so both are pinned here.
 */
class StreamingParseFileTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    private function writeTemp(string $contents): string
    {
        // tempnam() returns string|false; `?: ''` would also swallow an empty
        // string, so the failure is checked explicitly.
        $path = tempnam(sys_get_temp_dir(), 'ical-stream-');
        self::assertNotFalse($path, 'could not create a temporary file');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function calendar(string $body): string
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\n"
            . $body
            . "END:VCALENDAR\r\n";
    }

    private function event(int $n): string
    {
        return "BEGIN:VEVENT\r\nUID:e{$n}@example.com\r\nDTSTAMP:20260101T000000Z\r\n"
            . "DTSTART:20260101T000000Z\r\nSUMMARY:Event {$n}\r\nEND:VEVENT\r\n";
    }

    /** parseFile() and parse() must agree, including across chunk boundaries. */
    public function testParseFileMatchesParseOnAMultiChunkCalendar(): void
    {
        // Comfortably more than the 8 KB read chunk, so components straddle it.
        $body = '';
        for ($i = 0; $i < 200; $i++) {
            $body .= $this->event($i);
        }
        $ics = $this->calendar($body);
        $path = $this->writeTemp($ics);

        $fromString = (new Parser(Parser::LENIENT))->parse($ics);
        $fromFile = (new Parser(Parser::LENIENT))->parseFile($path);

        self::assertCount(200, $fromFile->getComponents('VEVENT'));
        self::assertCount(
            count($fromString->getComponents('VEVENT')),
            $fromFile->getComponents('VEVENT')
        );
        self::assertSame($fromString->getProductId(), $fromFile->getProductId());
    }

    /**
     * A folded line split across the 8 KB boundary must still unfold, and must
     * unfold identically to the in-memory path. The chunked reader carries a
     * pending line between chunks precisely for this.
     *
     * Note the expected value has no space at the join: RFC 5545 §3.1 unfolds by
     * removing the CRLF *and* the single whitespace character following it, so
     * that space is the fold marker rather than content.
     */
    public function testFoldedLineSpanningAChunkBoundaryIsUnfolded(): void
    {
        $padding = str_repeat($this->event(0), 120); // pushes past one chunk
        $folded = "BEGIN:VEVENT\r\nUID:folded@example.com\r\nDTSTAMP:20260101T000000Z\r\n"
            . "DTSTART:20260101T000000Z\r\nSUMMARY:Start of a long summary\r\n"
            . " continued across a fold\r\nEND:VEVENT\r\n";

        $ics = $this->calendar($padding . $folded);
        $path = $this->writeTemp($ics);

        $summaries = static function (\Icalendar\Component\VCalendar $calendar): array {
            $found = [];
            foreach ($calendar->getComponents('VEVENT') as $event) {
                $summary = $event->getProperty('SUMMARY');
                if ($summary !== null) {
                    $found[] = $summary->getValue()->getRawValue();
                }
            }

            return $found;
        };

        $fromFile = $summaries((new Parser(Parser::LENIENT))->parseFile($path));

        self::assertContains('Start of a long summarycontinued across a fold', $fromFile);
        // The guarantee that matters: streaming and in-memory agree.
        self::assertSame($summaries((new Parser(Parser::LENIENT))->parse($ics)), $fromFile);
    }

    /** A final line with no trailing CRLF must not be dropped. */
    public function testFinalLineWithoutTrailingCrlfIsParsed(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\n"
            . $this->event(1)
            . 'END:VCALENDAR'; // deliberately unterminated

        $path = $this->writeTemp($ics);
        $calendar = (new Parser(Parser::LENIENT))->parseFile($path);

        self::assertCount(1, $calendar->getComponents('VEVENT'));
    }

    /**
     * Warnings are recorded by the lexer only as its generator is consumed, so
     * they are transferred after the build rather than before it. If that
     * ordering regressed, lenient parseFile() would silently report none.
     */
    public function testLenientParseFileStillReportsLexerWarnings(): void
    {
        $ics = $this->calendar("BEGIN:VEVENT\r\nUID:w@example.com\r\nDTSTAMP:20260101T000000Z\r\n"
            . "this line has no colon\r\nEND:VEVENT\r\n");

        $path = $this->writeTemp($ics);
        $parser = new Parser(Parser::LENIENT);
        $parser->parseFile($path);

        self::assertNotEmpty($parser->getWarnings(), 'malformed line must still warn');
    }

    public function testStrictParseFileStillThrowsOnMalformedLine(): void
    {
        $ics = $this->calendar("BEGIN:VEVENT\r\nUID:s@example.com\r\n"
            . "this line has no colon\r\nEND:VEVENT\r\n");

        $path = $this->writeTemp($ics);

        $this->expectException(ParseException::class);
        (new Parser(Parser::STRICT))->parseFile($path);
    }

    /**
     * The XXE scan no longer sees the whole file, so it reads in chunks and
     * carries a tail between them. A marker landing exactly across the boundary
     * is the case that overlap exists for -- and the case a naive chunked scan
     * would miss.
     *
     * @return array<string, array{int}>
     */
    public static function xxeOffsetProvider(): array
    {
        return [
            'near the start' => [16],
            'just before the 8KB boundary' => [8192 - 4],
            'exactly on the boundary' => [8192],
            'just after the boundary' => [8192 + 4],
            'deep in the third chunk' => [8192 * 2 + 100],
        ];
    }

    #[DataProvider('xxeOffsetProvider')]
    public function testXxeMarkerIsDetectedAtAnyOffsetIncludingChunkBoundaries(int $offset): void
    {
        $prefix = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\n";
        $padding = str_repeat('X', max(0, $offset - strlen($prefix)));
        $contents = $prefix . $padding . '<!ENTITY xxe SYSTEM "file:///etc/passwd">' . "\r\nEND:VCALENDAR\r\n";

        $path = $this->writeTemp($contents);

        $this->expectException(ParseException::class);
        (new Parser(Parser::LENIENT))->parseFile($path);
    }

    public function testDoctypeMarkerIsAlsoDetected(): void
    {
        $path = $this->writeTemp("BEGIN:VCALENDAR\r\n<!DOCTYPE foo>\r\nEND:VCALENDAR\r\n");

        $this->expectException(ParseException::class);
        (new Parser(Parser::LENIENT))->parseFile($path);
    }

    /**
     * The scan is case-insensitive, so a lower-case marker must be caught too.
     * Every other marker case here is upper-case, which would let a
     * case-sensitive comparison pass unnoticed.
     */
    public function testLowerCaseMarkerIsDetected(): void
    {
        $path = $this->writeTemp("BEGIN:VCALENDAR\r\n<!entity xxe SYSTEM \"file:///etc/passwd\">\r\nEND:VCALENDAR\r\n");

        $this->expectException(ParseException::class);
        (new Parser(Parser::LENIENT))->parseFile($path);
    }

    /**
     * The carried overlap is sized to the *longest* marker. `<!DOCTYPE` is one
     * character longer than `<!ENTITY`, so a file that splits it with 8 of its 9
     * characters before the boundary is only caught when the overlap is 8 rather
     * than 7 -- the case that distinguishes a correct overlap from a plausible
     * off-by-one or a shortest-marker calculation.
     */
    public function testLongestMarkerSplitAcrossBoundaryIsDetected(): void
    {
        $marker = '<!DOCTYPE foo>';
        $prefix = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\n";
        // Land exactly 8 of the marker's 9 significant characters in chunk one.
        $padding = str_repeat('X', 8192 - strlen($prefix) - 8);

        $path = $this->writeTemp($prefix . $padding . $marker . "\r\nEND:VCALENDAR\r\n");

        $this->expectException(ParseException::class);
        (new Parser(Parser::LENIENT))->parseFile($path);
    }

    /** A clean calendar must not be mistaken for an attack. */
    public function testOrdinaryCalendarIsNotFlagged(): void
    {
        $path = $this->writeTemp($this->calendar($this->event(1)));

        $calendar = (new Parser(Parser::LENIENT))->parseFile($path);
        self::assertCount(1, $calendar->getComponents('VEVENT'));
    }
}
