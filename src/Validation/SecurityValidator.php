<?php

declare(strict_types=1);

namespace Icalendar\Validation;

use Icalendar\Exception\ParseException;

/**
 * Security validator for preventing XXE, SSRF, and other security vulnerabilities
 * 
 * Implements security requirements:
 * - NFR-010: XXE Prevention in ATTACH properties
 * - NFR-011: Recursion depth limiting
 * - NFR-012: SSRF Prevention via URI validation
 * - NFR-013: Text sanitization
 */
class SecurityValidator
{
    /** @var int Maximum nesting depth for components */
    private int $maxDepth;

    /** @var array<string> Allowed URI schemes */
    private array $allowedSchemes;

    /** @var int Maximum size for data: URIs in bytes */
    private int $maxDataUriSize;

    /** @var array<string> Private IP ranges to block */
    private array $privateIpRanges;

    /**
     * @param array<string>|null $allowedSchemes
     * @param array<string>|null $privateIpRanges
     */
    public function __construct(
        int $maxDepth = 100,
        ?array $allowedSchemes = null,
        int $maxDataUriSize = 1048576, // 1MB
        ?array $privateIpRanges = null
    ) {
        $this->maxDepth = $maxDepth;
        $this->allowedSchemes = $allowedSchemes ?? ['http', 'https', 'mailto', 'tel', 'urn', 'data'];
        $this->maxDataUriSize = $maxDataUriSize;
        $this->privateIpRanges = $privateIpRanges ?? [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            '169.254.0.0/16',
            'fc00::/7',
            'fe80::/10',
            '::1/128',
        ];
    }

    /**
     * Validate URI to prevent XXE and SSRF attacks
     * 
     * @throws ParseException If URI is unsafe
     */
    public function validateUri(string $uri): void
    {
        $parsed = parse_url($uri);
        
        if ($parsed === false) {
            throw new ParseException(
                "Invalid URI format: {$uri}",
                ParseException::ERR_INVALID_URI
            );
        }

        $scheme = $parsed['scheme'] ?? '';
        
        // Check if scheme is allowed
        if (!in_array(strtolower($scheme), $this->allowedSchemes, true)) {
            throw new ParseException(
                "URI scheme '{$scheme}' is not allowed. Allowed schemes: " . implode(', ', $this->allowedSchemes),
                ParseException::ERR_SECURITY_INVALID_SCHEME
            );
        }

        // Special handling for data: URIs to prevent memory exhaustion
        if (strtolower($scheme) === 'data') {
            $this->validateDataUri($uri);
        }

        // Check for private IPs in http/https URIs (SSRF prevention)
        if (in_array(strtolower($scheme), ['http', 'https'], true)) {
            $this->validateNotPrivateIp($parsed['host'] ?? '');
        }
    }

    /**
     * Validate data: URI to prevent memory exhaustion
     * 
     * @throws ParseException If data URI is too large
     */
    private function validateDataUri(string $uri): void
    {
        // Extract the data portion after the comma
        $commaPos = strpos($uri, ',');
        if ($commaPos === false) {
            return; // No data portion, just metadata
        }

        $data = substr($uri, $commaPos + 1);
        
        // Check for base64 encoding in the metadata portion (before comma)
        $metadata = substr($uri, 0, $commaPos);
        $isBase64 = strpos($metadata, 'base64') !== false;
        
        if ($isBase64) {
            // Base64 encoded data - approximate size is 3/4 of base64 string
            $decodedSize = (int) (strlen($data) * 3 / 4);
        } else {
            // URL encoded data
            $decodedSize = strlen($data);
        }

        if ($decodedSize > $this->maxDataUriSize) {
            throw new ParseException(
                "data: URI exceeds maximum size of {$this->maxDataUriSize} bytes",
                ParseException::ERR_SECURITY_DATA_URI_TOO_LARGE
            );
        }
    }

    /**
     * Check if host is a private IP (SSRF prevention)
     * 
     * @throws ParseException If host is a private IP
     */
    private function validateNotPrivateIp(string $host): void
    {
        if (empty($host)) {
            return;
        }

        // Check if it's an IP address
        $ip = filter_var($host, FILTER_VALIDATE_IP);
        
        if ($ip === false) {
            // It's a hostname, resolve it
            $resolved = gethostbyname($host);
            if ($resolved === $host) {
                // Could not resolve, might be invalid but let it pass
                // The actual HTTP request will fail if it's invalid
                return;
            }
            $ip = $resolved;
        }

        // Check against private IP ranges
        foreach ($this->privateIpRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                throw new ParseException(
                    "Access to private IP address '{$ip}' is not allowed (SSRF protection)",
                    ParseException::ERR_SECURITY_PRIVATE_IP
                );
            }
        }
    }

    /**
     * Check if an IP address is within a CIDR range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        $parts = explode('/', $range, 2);
        if (count($parts) < 2) {
            return $ip === $range;
        }
        $rangeIp = $parts[0];
        $netmask = (int)$parts[1];
        
        // Handle edge cases
        if ($netmask === 0) {
            return true; // 0.0.0.0/0 matches everything
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4
            $ipLong = ip2long($ip);
            $rangeLong = ip2long($rangeIp);
            
            if ($ipLong === false || $rangeLong === false) {
                return false;
            }
            
            // For /32, it's an exact match
            if ($netmask === 32) {
                return $ipLong === $rangeLong;
            }
            
            $mask = -1 << (32 - $netmask);
            
            return ($ipLong & $mask) === ($rangeLong & $mask);
        } else {
            // IPv6
            $ipBin = inet_pton($ip);
            $rangeBin = inet_pton($rangeIp);
            
            if ($ipBin === false || $rangeBin === false) {
                return false;
            }

            $maskBits = $netmask;
            $ipBytes = unpack('C*', $ipBin);
            $rangeBytes = unpack('C*', $rangeBin);
            
            if ($ipBytes === false || $rangeBytes === false) {
                return false;
            }

            for ($i = 1; $i <= 16 && $maskBits > 0; $i++) {
                $bits = min(8, $maskBits);
                $mask = 0xFF << (8 - $bits);
                
                if (($ipBytes[$i] & $mask) !== ($rangeBytes[$i] & $mask)) {
                    return false;
                }
                
                $maskBits -= $bits;
            }
            
            return true;
        }
    }

    /**
     * Check recursion depth
     * 
     * @throws ParseException If depth exceeds maximum
     */
    public function checkDepth(int $currentDepth): void
    {
        if ($currentDepth > $this->maxDepth) {
            throw new ParseException(
                "Maximum nesting depth of {$this->maxDepth} exceeded. " .
                "This may indicate a maliciously crafted file or excessive nesting.",
                ParseException::ERR_SECURITY_DEPTH_EXCEEDED
            );
        }
    }

    /**
     * Get maximum allowed depth
     */
    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    /**
     * Set maximum allowed depth
     */
    public function setMaxDepth(int $maxDepth): self
    {
        $this->maxDepth = $maxDepth;
        return $this;
    }

    /**
     * Get allowed URI schemes
     * @return array<string>
     */
    public function getAllowedSchemes(): array
    {
        return $this->allowedSchemes;
    }

    /**
     * Set allowed URI schemes
     * @param array<string> $schemes
     */
    public function setAllowedSchemes(array $schemes): self
    {
        $this->allowedSchemes = $schemes;
        return $this;
    }

    /**
     * Get maximum data: URI size
     */
    public function getMaxDataUriSize(): int
    {
        return $this->maxDataUriSize;
    }

    /**
     * Set maximum data: URI size
     */
    public function setMaxDataUriSize(int $size): self
    {
        $this->maxDataUriSize = $size;
        return $this;
    }

    /**
     * Sanitize text output to remove null bytes and escape control characters
     * 
     * Implements NFR-013: Sanitize text output
     * - Strips null bytes (\x00)
     - Escapes control characters (\x01-\x1F except \t, \n, \r)
     * 
     * @param string $text Text to sanitize
     * @return string Sanitized text
     */
    public function sanitizeText(string $text): string
    {
        // Remove null bytes
        $text = str_replace("\x00", '', $text);
        
        // Escape control characters (except tab, newline, carriage return)
        $result = '';
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            $ord = ord($char);
            
            if ($ord >= 0x01 && $ord <= 0x1F && $ord !== 0x09 && $ord !== 0x0A && $ord !== 0x0D) {
                // Control character - escape it as \xNN
                $result .= sprintf('\\x%02x', $ord);
            } else {
                $result .= $char;
            }
        }
        
        return $result;
    }
}