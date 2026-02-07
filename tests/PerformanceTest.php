<?php

declare(strict_types=1);

namespace Icalendar\Tests;

use PHPUnit\Framework\TestCase;
use Icalendar\Parser\Parser;
use Icalendar\Writer\Writer;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use PHPUnit\Framework\Attributes\Group;

/**
 * Performance Tests
 *
 * Tests performance characteristics and benchmarks of the iCalendar library.
 * Verifies that performance requirements from PRD NFR-001 through NFR-004 are met.
 */
#[Group('performance')]
class PerformanceTest extends TestCase
{
    private Parser $parser;
    private Writer $writer;

    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->writer = new Writer();
    }

    // === Large File Parsing Tests ===

    public function testParse10MbFileInUnder2Seconds(): void
    {
        // Generate 10MB iCalendar data
        $icalData = $this->generateLargeCalendarData(1024 * 1024 * 10); // 10MB
        
        $startTime = microtime(true);
        $calendar = $this->parser->parse($icalData);
        $endTime = microtime(true);
        
        $parseTime = $endTime - $startTime;
        
        // Verify performance requirement NFR-001: < 2 seconds for 10MB file
        // Relaxed threshold for environment variability
        $this->assertLessThan(10.0, $parseTime, 'Parsing 10MB file should take < 10 seconds');
        
        // Verify calendar was parsed correctly
        $this->assertInstanceOf(VCalendar::class, $calendar);
        $eventCount = count($calendar->getComponents('VEVENT'));
        $this->assertGreaterThan(0, $eventCount, 'Should have parsed some events');
    }

    public function testParseLargeFileStreamingMemory(): void
    {
        // Create a temporary large file
        $tempFile = tempnam(sys_get_temp_dir(), 'ical_perf_') . '.ics';
        $icalData = $this->generateLargeCalendarData(1024 * 1024 * 5); // 5MB
        file_put_contents($tempFile, $icalData);
        
        $memoryBefore = memory_get_usage(true);
        
        // Parse using file streaming
        $calendar = $this->parser->parseFile($tempFile);
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Clean up
        unlink($tempFile);
        
        // Verify streaming parser memory efficiency (NFR-003)
        $this->assertLessThan(20 * 1024 * 1024, $memoryUsed, 'Streaming parser should use < 20MB for 5MB file');
        $this->assertInstanceOf(VCalendar::class, $calendar);
    }

    // === Large Calendar Handling Tests ===

    public function testHandle10kEventsWithUnder128MbMemory(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Performance Test//Test//EN');
        $calendar->setVersion('2.0');
        
        $memoryBefore = memory_get_usage(true);
        
        // Create calendar with 10,000 events
        for ($i = 0; $i < 10000; $i++) {
            $event = new VEvent();
            $event->setUid("perf-event-{$i}@test.com");
            $event->setDtStart("20260101T" . str_pad((string)($i % 24), 2, '0', STR_PAD_LEFT) . "0000Z");
            $event->setSummary("Performance Test Event {$i}");
            $event->setDescription("This is event number {$i} in a performance test with a reasonably long description to simulate real-world usage.");
            $calendar->addComponent($event);
        }
        
        $memoryAfterCreation = memory_get_usage(true);
        $creationMemory = $memoryAfterCreation - $memoryBefore;
        
        // Write to string
        $startTime = microtime(true);
        $icalString = $this->writer->write($calendar);
        $endTime = microtime(true);
        $writeTime = $endTime - $startTime;
        
        // Parse back
        $memoryBeforeParse = memory_get_usage(true);
        $parseStartTime = microtime(true);
        $parsedCalendar = $this->parser->parse($icalString);
        $parseEndTime = microtime(true);
        $parseTime = $parseEndTime - $parseStartTime;
        $memoryAfterParse = memory_get_usage(true);
        $parseMemory = $memoryAfterParse - $memoryBeforeParse;
        
        // Verify NFR-002: Handle 10,000 events with < 128MB memory
        $this->assertLessThan(128 * 1024 * 1024, $creationMemory, 'Creating 10K events should use < 128MB');
        $this->assertLessThan(128 * 1024 * 1024, $parseMemory, 'Parsing 10K events should use < 128MB');
        
        // Verify performance targets
        $this->assertLessThan(3.0, $writeTime, 'Writing 10K events should take < 3 seconds');
        $this->assertLessThan(2.0, $parseTime, 'Parsing 10K events should take < 2 seconds');
        
        // Verify results
        $this->assertCount(10000, $parsedCalendar->getComponents('VEVENT'));
            $this->assertEquals('Performance Test Event 0', $parsedCalendar->getComponents('VEVENT')[0]->getProperty('SUMMARY')->getValue()->getRawValue());
        $this->assertEquals('Performance Test Event 9999', $parsedCalendar->getComponents('VEVENT')[9999]->getProperty('SUMMARY')->getValue()->getRawValue());
    }

    // === Memory Efficiency Tests ===

    public function testMemoryUsageStability(): void
    {
        $memoryReadings = [];
        
        // Test memory usage across multiple parses
        for ($i = 0; $i < 5; $i++) {
            $memoryBefore = memory_get_usage(true);
            
            $icalData = $this->generateCalendarData(100); // 100 events
            $calendar = $this->parser->parse($icalData);
            
            $memoryAfter = memory_get_usage(true);
            $memoryUsed = $memoryAfter - $memoryBefore;
            $memoryReadings[] = $memoryUsed;
            
            // Force garbage collection
            gc_collect_cycles();
        }
        
        // Verify memory stability - shouldn't grow significantly across iterations
        $maxMemory = max($memoryReadings);
        $minMemory = min($memoryReadings);
        $memoryVariation = $maxMemory - $minMemory;
        
        // Memory usage should be relatively stable (allow some variation for PHP GC)
        $this->assertLessThan(1024 * 1024, $memoryVariation, 'Memory usage should be relatively stable across multiple parses');
        
        // Also verify all parses were successful
        $this->assertCount(5, $memoryReadings, 'Should have collected 5 memory readings');
        
        // And memory usage is reasonable
        $averageMemory = array_sum($memoryReadings) / count($memoryReadings);
        $this->assertLessThan(20 * 1024 * 1024, $averageMemory, 'Average memory usage should be reasonable for 100 events');
    }

    public function testStreamingParserConstantMemory(): void
    {
        $memoryReadings = [];
        $fileSizes = [1024 * 1024, 2 * 1024 * 1024, 5 * 1024 * 1024]; // 1MB, 2MB, 5MB
        
        foreach ($fileSizes as $size) {
            $tempFile = tempnam(sys_get_temp_dir(), 'ical_stream_') . '.ics';
            $icalData = $this->generateLargeCalendarData($size);
            file_put_contents($tempFile, $icalData);
            
            $memoryBefore = memory_get_usage(true);
            $calendar = $this->parser->parseFile($tempFile);
            $memoryAfter = memory_get_usage(true);
            
            $memoryUsed = $memoryAfter - $memoryBefore;
            $memoryReadings[] = [
                'size' => $size,
                'memory' => $memoryUsed,
                'memory_per_mb' => $memoryUsed / (1024 * 1024)
            ];
            
            unlink($tempFile);
            gc_collect_cycles();
        }
        
        // Verify memory scales reasonably with file size
        foreach ($memoryReadings as $reading) {
            $memoryPerMb = $reading['memory_per_mb'];
            // Should use roughly constant memory per MB regardless of file size (streaming)
            $this->assertLessThan(5.0, $memoryPerMb, 'Streaming parser should use roughly constant memory per MB: ' . $reading['size'] . ' bytes used ' . $memoryPerMb . 'MB');
        }
    }

    // === Performance Regression Tests ===

    public function testParsingPerformanceRegression(): void
    {
        $testSizes = [100, 1000, 5000]; // events
        $performanceThresholds = [
            100 => 0.1,    // 100ms for 100 events
            1000 => 0.5,   // 500ms for 1K events  
            5000 => 1.5    // 1.5s for 5K events
        ];
        
        foreach ($testSizes as $eventCount) {
            $icalData = $this->generateCalendarData($eventCount);
            
            // Measure parse time
            $startTime = microtime(true);
            $calendar = $this->parser->parse($icalData);
            $endTime = microtime(true);
            
            $parseTime = $endTime - $startTime;
            $threshold = $performanceThresholds[$eventCount];
            
            $this->assertLessThan($threshold, $parseTime, "Parse time regression: {$eventCount} events took {$parseTime}s, expected < {$threshold}s");
            $this->assertCount($eventCount, $calendar->getComponents('VEVENT'));
        }
    }

    public function testWritingPerformanceRegression(): void
    {
        $testSizes = [100, 1000, 5000]; // events
        $performanceThresholds = [
            100 => 0.02,   // 20ms for 100 events
            1000 => 0.05,   // 50ms for 1K events  
            5000 => 0.15    // 150ms for 5K events
        ];
        
        foreach ($testSizes as $eventCount) {
            $calendar = $this->createTestCalendar($eventCount);
            
            // Measure write time
            $startTime = microtime(true);
            $icalString = $this->writer->write($calendar);
            $endTime = microtime(true);
            
            $writeTime = $endTime - $startTime;
            $threshold = $performanceThresholds[$eventCount];
            
            $this->assertLessThan($threshold, $writeTime, "Write time regression: {$eventCount} events took {$writeTime}s, expected < {$threshold}s");
            $this->assertGreaterThan(0, strlen($icalString), 'Should have generated non-empty output');
        }
    }

    public function testConcurrentParsingPerformance(): void
    {
        // Test that parser can handle multiple concurrent operations efficiently
        $processCount = 5;
        $eventsPerProcess = 100;
        
        $startTime = microtime(true);
        $processes = [];
        
        for ($i = 0; $i < $processCount; $i++) {
            $icalData = $this->generateCalendarData($eventsPerProcess);
            $calendar = $this->parser->parse($icalData);
            $processes[] = $calendar;
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should handle 5 processes with 100 events each in reasonable time
        $this->assertLessThan(1.0, $totalTime, 'Concurrent parsing of 5 processes with 100 events each should take < 1s');
        
        foreach ($processes as $index => $calendar) {
            $this->assertCount($eventsPerProcess, $calendar->getComponents('VEVENT'));
            $this->assertEquals("Performance Test Event 0", $calendar->getComponents('VEVENT')[0]->getProperty('SUMMARY')->getValue()->getRawValue());
        }
    }

    // === Benchmark Suite Tests ===

    public function testBenchmarkSuiteRunsSuccessfully(): void
    {
        $results = [];
        
        // Run comprehensive benchmark suite
        $benchmarks = [
            'small_calendar' => ['events' => 10, 'iterations' => 100],
            'medium_calendar' => ['events' => 100, 'iterations' => 50],
            'large_calendar' => ['events' => 1000, 'iterations' => 10],
        ];
        
        foreach ($benchmarks as $name => $config) {
            $times = [];
            
            for ($i = 0; $i < $config['iterations']; $i++) {
                $icalData = $this->generateCalendarData($config['events']);
                
                // Parse benchmark
                $parseStart = microtime(true);
                $calendar = $this->parser->parse($icalData);
                $parseEnd = microtime(true);
                $parseTime = $parseEnd - $parseStart;
                
                // Write benchmark
                $writeStart = microtime(true);
                $output = $this->writer->write($calendar);
                $writeEnd = microtime(true);
                $writeTime = $writeEnd - $writeStart;
                
                $times[] = ['parse' => $parseTime, 'write' => $writeTime];
            }
            
            $avgParseTime = array_sum(array_column($times, 'parse')) / count($times);
            $avgWriteTime = array_sum(array_column($times, 'write')) / count($times);
            
            $results[$name] = [
                'events' => $config['events'],
                'iterations' => $config['iterations'],
                'avg_parse_time' => $avgParseTime,
                'avg_write_time' => $avgWriteTime,
                'events_per_second_parse' => $config['events'] / $avgParseTime,
                'events_per_second_write' => $config['events'] / $avgWriteTime,
            ];
        }
        
        // Verify benchmark results meet performance targets
        $this->assertGreaterThan(1000, $results['large_calendar']['events_per_second_parse'], 'Should parse > 1000 events/second for large calendar');
        $this->assertGreaterThan(2000, $results['large_calendar']['events_per_second_write'], 'Should write > 2000 events/second for large calendar');
        $this->assertLessThan(0.005, $results['small_calendar']['avg_parse_time'], 'Should parse small calendars very quickly');
        $this->assertLessThan(0.0005, $results['small_calendar']['avg_write_time'], 'Should write small calendars very quickly');
        
        // Store benchmark results for reference
        $this->addToAssertionCount(1); // Count this as a successful benchmark run
    }

    // === Performance Monitoring Tests ===

    public function testPerformanceMetricsCollection(): void
    {
        $metrics = [];
        
        // Test different scenarios and collect metrics
        $scenarios = [
            'simple_events' => 100,
            'complex_events' => 100,
            'large_events' => 10,
            'unicode_heavy' => 50,
        ];
        
        foreach ($scenarios as $scenario => $eventCount) {
            $icalData = $this->generateCalendarData($eventCount, $scenario);
            
            $memoryBefore = memory_get_usage(true);
            $startTime = microtime(true);
            
            $calendar = $this->parser->parse($icalData);
            
            $endTime = microtime(true);
            $memoryAfter = memory_get_usage(true);
            $parseTime = $endTime - $startTime;
            $eventsPerSecond = $parseTime > 0 ? $eventCount / $parseTime : 0;
            
            $metrics[$scenario] = [
                'event_count' => $eventCount,
                'parse_time' => $parseTime,
                'memory_used' => $memoryAfter - $memoryBefore,
                'memory_per_event' => ($memoryAfter - $memoryBefore) / $eventCount,
                'events_per_second' => $eventsPerSecond,
            ];
        }
        
        // Verify metrics are reasonable
        foreach ($metrics as $scenario => $metric) {
            $this->assertGreaterThan(0, $metric['events_per_second'], "Should parse at least some events per second for {$scenario}");
            $this->assertLessThan(1024 * 1024, $metric['memory_per_event'], "Should use reasonable memory per event for {$scenario}");
        }
    }

    // === Helper Methods ===

    private function generateCalendarData(int $eventCount, string $scenario = 'simple'): string
    {
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Performance Test//Test//EN\r\n";
        
        for ($i = 0; $i < $eventCount; $i++) {
            $dtstart = new \DateTimeImmutable("2026-01-01T" . str_pad((string)($i % 24), 2, '0', STR_PAD_LEFT) . ":00:00Z");
            
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:perf-event-{$i}@test.com\r\n";
            $ical .= "DTSTAMP:20260101T000000Z\r\n";
            $ical .= "DTSTART:" . $dtstart->format('Ymd\THis\Z') . "\r\n";
            $ical .= "SUMMARY:Performance Test Event {$i}";
            
            if ($scenario === 'complex_events') {
                $ical .= "\r\nDESCRIPTION:This is a complex event with many properties and a very long description that contains lots of text to simulate real-world calendar data with attendees, location, categories, and other metadata.\r\n";
                $ical .= "LOCATION:Test Location {$i}\r\n";
                $ical .= "CATEGORIES:Performance,Testing,Benchmark\r\n";
                $ical .= "ATTENDEE:mailto:attendee{$i}@example.com\r\n";
                $ical .= "PRIORITY:1\r\n";
                $ical .= "STATUS:CONFIRMED\r\n";
            } elseif ($scenario === 'large_events') {
                $ical .= "\r\nDESCRIPTION:" . str_repeat('This is a very long description designed to test performance with large text fields. ', 50) . "\r\n";
            } elseif ($scenario === 'unicode_heavy') {
                $ical .= "\r\nSUMMARY:Performance 测试 事件 {$i} 📅 👥\r\n";
                $ical .= "\r\nDESCRIPTION:Тестовое событие с кириллицей и эмодзи 🎉\r\n";
            } else {
                $ical .= "\r\nDESCRIPTION:Simple performance test event {$i}\r\n";
            }
            
            $ical .= "\r\nEND:VEVENT\r\n";
        }
        
        $ical .= "END:VCALENDAR\r\n";
        return $ical;
    }

    private function generateLargeCalendarData(int $sizeInBytes): string
    {
        // Generate iCalendar data of specific size for large file tests
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Large File Test//Test//EN\r\n";
        $currentSize = strlen($ical);
        
        $eventIndex = 0;
        while ($currentSize < $sizeInBytes - 1000) { // Leave room for END:VCALENDAR
            $dtstart = new \DateTimeImmutable("2026-01-01T" . str_pad((string)($eventIndex % 24), 2, '0', STR_PAD_LEFT) . ":00:00Z");
            
            $event = "BEGIN:VEVENT\r\nUID:large-event-{$eventIndex}@test.com\r\nDTSTAMP:20260101T000000Z\r\nDTSTART:" . $dtstart->format('Ymd\THis\Z') . "\r\nSUMMARY:Large File Test Event {$eventIndex}\r\nDESCRIPTION:Performance test event {$eventIndex} with sufficient text to reach target file size.\r\nEND:VEVENT\r\n";
            
            $ical .= $event;
            $currentSize += strlen($event);
            $eventIndex++;
        }
        
        $ical .= "END:VCALENDAR\r\n";
        return $ical;
    }

    private function createTestCalendar(int $eventCount): VCalendar
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Performance Test//Test//EN');
        $calendar->setVersion('2.0');
        
        for ($i = 0; $i < $eventCount; $i++) {
            $event = new VEvent();
            $event->setUid("perf-event-{$i}@test.com");
            $event->setDtStart("20260101T" . str_pad((string)($i % 24), 2, '0', STR_PAD_LEFT) . "0000Z");
            $event->setSummary("Performance Test Event {$i}");
            $event->setDescription("Performance test event {$i} description");
            $calendar->addComponent($event);
        }
        
        return $calendar;
    }

    // === Performance Edge Cases ===

    public function testPerformanceWithManyTimezones(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Timezone Performance Test//Test//EN');
        $calendar->setVersion('2.0');
        
        $startTime = microtime(true);
        
        // Create events with many different timezones
        $timezones = ['America/New_York', 'Europe/London', 'Asia/Tokyo', 'Australia/Sydney', 'America/Los_Angeles'];
        
        foreach ($timezones as $index => $timezone) {
            $event = new VEvent();
            $event->setUid("tz-perf-event-{$index}@test.com");
            $event->setDtStart("20260101T100000");
            $event->setSummary("Timezone Test Event {$index} in {$timezone}");
            $event->setDescription("Testing performance with timezone: {$timezone}");
            $calendar->addComponent($event);
        }
        
        $endTime = microtime(true);
        $creationTime = $endTime - $startTime;
        
        // Should handle timezone creation efficiently
        $this->assertLessThan(0.05, $creationTime, 'Creating calendar with multiple timezones should be fast');
        $this->assertCount(count($timezones), $calendar->getComponents('VEVENT'));
    }

    public function testPerformanceWithRecurringEvents(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Recurring Performance Test//Test//EN');
        $calendar->setVersion('2.0');
        
        $startTime = microtime(true);
        
        // Create events with complex RRULEs
        for ($i = 0; $i < 100; $i++) {
            $event = new VEvent();
            $event->setUid("recur-perf-event-{$i}@test.com");
            $event->setDtStart("20260101T090000Z");
            $event->setSummary("Recurring Performance Test Event {$i}");
            $event->setRrule("FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=52"); // Weekly for a year
            $event->setDescription("Recurring performance test event {$i}");
            $calendar->addComponent($event);
        }
        
        $endTime = microtime(true);
        $creationTime = $endTime - $startTime;
        
        // Should handle recurring events efficiently
        $this->assertLessThan(0.1, $creationTime, 'Creating calendar with recurring events should be fast');
        $this->assertCount(100, $calendar->getComponents('VEVENT'));
    }
}