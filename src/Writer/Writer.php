<?php

declare(strict_types=1);

namespace Icalendar\Writer;

use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VCalendar;
use Icalendar\Exception\InvalidDataException;

/**
 * Main writer implementation for iCalendar output
 *
 * Orchestrates the writing process:
 * - Uses ComponentWriter to serialize components
 * - Uses ContentLineWriter for line folding
 * - Manages the overall output structure
 */
class Writer implements WriterInterface
{
    private ContentLineWriter $contentLineWriter;
    private ComponentWriter $componentWriter;

    public function __construct(?ComponentWriter $componentWriter = null, ?ContentLineWriter $contentLineWriter = null)
    {
        $this->componentWriter = $componentWriter ?? new ComponentWriter();
        $this->contentLineWriter = $contentLineWriter ?? new ContentLineWriter(75, true);
    }

    #[\Override]
    public function write(VCalendar $calendar): string
    {
        $output = $this->writeComponent($calendar);

        return $this->contentLineWriter->write($output);
    }

    private function writeComponent(ComponentInterface $component): string
    {
        $lines = [];
        $name = $component->getName();

        $lines[] = 'BEGIN:' . $name;

        $properties = $component->getProperties();
        foreach ($properties as $property) {
            $lines[] = $this->componentWriter->getPropertyWriter()->write($property);
        }

        $subComponents = $component->getComponents();
        foreach ($subComponents as $subComponent) {
            $lines[] = $this->writeComponent($subComponent);
        }

        $lines[] = 'END:' . $name;

        return implode("\r\n", $lines);
    }

    #[\Override]
    public function writeToFile(VCalendar $calendar, string $filepath): void
    {
        $content = $this->write($calendar);
        $result = file_put_contents($filepath, $content, LOCK_EX);

        if ($result === false) {
            throw new \RuntimeException(
                "Failed to write to file: {$filepath}",
                0
            );
        }
    }

    #[\Override]
    public function setLineFolding(bool $fold, int $maxLength = 75): void
    {
        $this->contentLineWriter->setFoldingEnabled($fold);
        $this->contentLineWriter->setMaxLength($maxLength);
    }

    /**
     * Get the content line writer instance
     *
     * Provides access to the underlying ContentLineWriter for advanced
     * configuration of line folding and output formatting.
     *
     * @return ContentLineWriter The content line writer instance
     */
    public function getContentLineWriter(): ContentLineWriter
    {
        return $this->contentLineWriter;
    }

    /**
     * Get the component writer instance
     *
     * Provides access to the underlying ComponentWriter for advanced
     * configuration of component serialization behavior.
     *
     * @return ComponentWriter The component writer instance
     */
    public function getComponentWriter(): ComponentWriter
    {
        return $this->componentWriter;
    }
}
