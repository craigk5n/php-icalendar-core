<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use DateInterval;
use DateTimeInterface;

/**
 * Writer for PERIOD values (start/end or start/duration)
 * Supports multiple comma-separated periods.
 */
class PeriodWriter implements ValueWriterInterface
{
    private DateTimeWriter $dateTimeWriter;
    private DurationWriter $durationWriter;

    public function __construct()
    {
        $this->dateTimeWriter = new DateTimeWriter();
        $this->durationWriter = new DurationWriter();
    }

    #[\Override]
    public function write(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException('PeriodWriter expects array or string, got ' . gettype($value));
        }

        if (empty($value)) {
            return '';
        }

        // Check if it's a single period or a list of periods
        // A single period can be [start, end/duration] or ['start' => ..., 'end' => ...]
        if ($this->isSinglePeriod($value)) {
            return $this->writeSinglePeriod($value);
        }

        // It's a list of periods
        return implode(',', array_map([$this, 'writeSinglePeriod'], $value));
    }

    private function isSinglePeriod(array $value): bool
    {
        if (isset($value['start']) || isset($value['end']) || isset($value['duration'])) return true;
        if (count($value) === 2 && isset($value[0]) && $value[0] instanceof DateTimeInterface) return true;
        return false;
    }

    private function writeSinglePeriod(mixed $period): string
    {
        if (!is_array($period)) {
            throw new \InvalidArgumentException('Each period must be an array');
        }

        $start = $period['start'] ?? $period[0] ?? null;
        $end = $period['end'] ?? $period[1] ?? null;
        $duration = $period['duration'] ?? (isset($period[1]) && $period[1] instanceof DateInterval ? $period[1] : null);

        if (!$start instanceof DateTimeInterface) {
            throw new \InvalidArgumentException('Period start must be DateTimeInterface');
        }

        $parts = [$this->dateTimeWriter->write($start)];

        if ($end instanceof DateTimeInterface) {
            $parts[] = $this->dateTimeWriter->write($end);
        } elseif ($duration instanceof DateInterval) {
            $parts[] = $this->durationWriter->write($duration);
        } else {
            throw new \InvalidArgumentException('Period must have end (DateTimeInterface) or duration (DateInterval)');
        }

        return implode('/', $parts);
    }

    #[\Override]
    public function getType(): string
    {
        return 'PERIOD';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_array($value) || is_string($value);
    }
}
