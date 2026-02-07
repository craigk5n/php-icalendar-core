<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use Icalendar\Exception\InvalidDataException;

/**
 * Factory for value writers
 *
 * Dispatches to appropriate writer based on data type
 */
class ValueWriterFactory
{
    /** @var array<string, ValueWriterInterface> */
    private array $writers = [];

    /** @var array<string, string> Type to class mapping */
    private array $typeMap = [
        'DATE' => DateWriter::class,
        'DATE-TIME' => DateTimeWriter::class,
        'TEXT' => TextWriter::class,
        'DURATION' => DurationWriter::class,
        'BINARY' => BinaryWriter::class,
        'INTEGER' => IntegerWriter::class,
        'FLOAT' => FloatWriter::class,
        'BOOLEAN' => BooleanWriter::class,
        'URI' => UriWriter::class,
        'CAL-ADDRESS' => CalAddressWriter::class,
        'TIME' => TimeWriter::class,
        'UTC-OFFSET' => UtcOffsetWriter::class,
        'PERIOD' => PeriodWriter::class,
        'RECUR' => RecurWriter::class,
    ];

    public function __construct()
    {
        $this->initializeWriters();
    }

    /**
     * Initialize all built-in writers
     */
    private function initializeWriters(): void
    {
        foreach ($this->typeMap as $type => $class) {
            $instance = new $class();
            if ($instance instanceof ValueWriterInterface) {
                $this->writers[$type] = $instance;
            }
        }
    }

    /**
     * Get writer for a specific data type
     */
    public function getWriter(string $type): ValueWriterInterface
    {
        $type = strtoupper($type);

        if (!isset($this->writers[$type])) {
            throw new InvalidDataException(
                "Unknown value type: {$type}",
                InvalidDataException::ERR_UNSUPPORTED_TYPE
            );
        }

        return $this->writers[$type];
    }

    /**
     * Write a value using the specified type
     *
     * @param mixed $value The value to write
     * @param string $type The data type
     * @return string The serialized value
     */
    public function write(mixed $value, string $type): string
    {
        $writer = $this->getWriter($type);
        return $writer->write($value);
    }

    /**
     * Check if a writer exists for the given type
     */
    public function hasWriter(string $type): bool
    {
        return isset($this->writers[strtoupper($type)]);
    }

    /**
     * Get list of supported types
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array
    {
        return array_keys($this->writers);
    }

    /**
     * Register a custom writer
     */
    public function registerWriter(string $type, ValueWriterInterface $writer): void
    {
        $this->writers[strtoupper($type)] = $writer;
    }

    /**
     * Clear all writers (useful for testing)
     */
    public function clearWriters(): void
    {
        $this->writers = [];
    }
}