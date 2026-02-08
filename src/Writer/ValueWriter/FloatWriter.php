<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for FLOAT values
 */
class FloatWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (!is_float($value) && !is_int($value)) {
            throw new \InvalidArgumentException('FloatWriter expects float or int, got ' . gettype($value));
        }

        $floatVal = (float) $value;

        // Handle special float values
        if (is_nan($floatVal)) {
            return 'NAN';
        }
        if (is_infinite($floatVal)) {
            return $floatVal > 0 ? 'INF' : '-INF';
        }

        // For integers, return plain integer string
        if (is_int($value)) {
            return (string) $value;
        }

        // Handle zero (including -0)
        if ($floatVal == 0.0) {
            return '0';
        }

        // Use PHP's native string conversion (uses precision=14 ini setting)
        $str = (string) $floatVal;

        // If PHP used scientific notation, convert to decimal
        if (stripos($str, 'E') !== false) {
            $str = $this->scientificToDecimal($str);
        }

        return $str;
    }

    /**
     * Convert scientific notation string to decimal notation
     */
    private function scientificToDecimal(string $str): string
    {
        [$significand, $exponent] = explode('E', strtoupper($str));
        $exp = (int) $exponent;

        $negative = str_starts_with($significand, '-');
        $significand = ltrim($significand, '-+');

        $parts = explode('.', $significand);
        $digits = $parts[0] . ($parts[1] ?? '');
        $decimalPos = strlen($parts[0]) + $exp;

        if ($decimalPos >= strlen($digits)) {
            $result = $digits . str_repeat('0', $decimalPos - strlen($digits));
        } elseif ($decimalPos <= 0) {
            $result = '0.' . str_repeat('0', -$decimalPos) . $digits;
        } else {
            $result = substr($digits, 0, $decimalPos) . '.' . substr($digits, $decimalPos);
        }

        // Remove trailing zeros after decimal point
        if (str_contains($result, '.')) {
            $result = rtrim(rtrim($result, '0'), '.');
        }

        return $negative ? '-' . $result : $result;
    }

    #[\Override]
    public function getType(): string
    {
        return 'FLOAT';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_float($value) || is_int($value);
    }
}