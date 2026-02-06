<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use DateInterval;
use DateTimeInterface;

/**
 * Writer for PERIOD values (start/end or start/duration)
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

    public function write(mixed $value): string
    {
        if (!is_array($value) || !isset($value['start'])) {
            throw new \InvalidArgumentException('PeriodWriter expects array with start and end/duration');
        }

        $start = $value['start'];
        $end = $value['end'] ?? null;
        $duration = $value['duration'] ?? null;

        if (!$start instanceof DateTimeInterface) {
            throw new \InvalidArgumentException('Period start must be DateTimeInterface');
        }

        $parts = [$this->dateTimeWriter->write($start)];

        if ($end !== null) {
            if (!$end instanceof DateTimeInterface) {
                throw new \InvalidArgumentException('Period end must be DateTimeInterface');
            }
            $parts[] = $this->dateTimeWriter->write($end);
        } elseif ($duration !== null) {
            if (!$duration instanceof DateInterval) {
                throw new \InvalidArgumentException('Period duration must be DateInterval');
            }
            $parts[] = $this->durationWriter->write($duration);
        } else {
            throw new \InvalidArgumentException('Period must have end or duration');
        }

        return implode('/', $parts);
    }

    public function getType(): string
    {
        return 'PERIOD';
    }

    public function canWrite(mixed $value): bool
    {
        return is_array($value) && isset($value['start']);
    }
}