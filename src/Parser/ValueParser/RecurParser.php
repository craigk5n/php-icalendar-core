<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Recurrence\RRule;
use Icalendar\Recurrence\RRuleParser;

/**
 * Parser for RECUR values (RRULE) according to RFC 5545 §3.3.10
 */
class RecurParser implements ValueParserInterface
{
    private bool $strict = false;

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public function parse(string $value, array $parameters = []): RRule
    {
        $parser = new RRuleParser();
        $parser->setStrict($this->strict);
        return $parser->parse($value);
    }

    public function getType(): string
    {
        return 'RECUR';
    }

    public function canParse(string $value): bool
    {
        $parser = new RRuleParser();
        $parser->setStrict($this->strict);
        return $parser->canParse($value);
    }
}