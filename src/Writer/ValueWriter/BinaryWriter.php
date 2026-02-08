<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for BINARY values (Base64 encoded)
 */
class BinaryWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('BinaryWriter expects string, got ' . gettype($value));
        }

        return $this->encodeBase64($value);
    }

    /**
     * Encode binary data to base64
     *
     * ContentLineWriter handles line folding for the entire content line,
     * so we just return the raw base64 string here.
     */
    private function encodeBase64(string $data): string
    {
        return base64_encode($data);
    }

    #[\Override]
    public function getType(): string
    {
        return 'BINARY';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_string($value);
    }
}