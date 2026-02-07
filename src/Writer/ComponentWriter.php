<?php

declare(strict_types=1);

namespace Icalendar\Writer;

use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VTodo;
use Icalendar\Component\VJournal;
use Icalendar\Component\VFreeBusy;
use Icalendar\Component\VTimezone;
use Icalendar\Component\Standard;
use Icalendar\Component\Daylight;
use Icalendar\Component\VAlarm;
use Icalendar\Property\GenericProperty; // Needed if ComponentInterface's getProperties() can return generic ones.
use Icalendar\Value\TextValue; // Not directly used in writer logic but relevant for context.

/**
 * Serializes components to iCalendar format
 *
 * Handles component serialization according to RFC 5545:
 * - Generates BEGIN/END markers for all component types
 * - Serializes properties in the order they were added
 * - Serializes sub-components recursively
 * - Handles empty components correctly
 * - Implements conflict resolution for DESCRIPTION vs STYLED-DESCRIPTION based on RFC 9073 for writing.
 */
class ComponentWriter
{
    private PropertyWriter $propertyWriter;
    private ContentLineWriter $contentLineWriter;

    public function __construct(?PropertyWriter $propertyWriter = null, ?ContentLineWriter $contentLineWriter = null)
    {
        $this->propertyWriter = $propertyWriter ?? new PropertyWriter();
        $this->contentLineWriter = $contentLineWriter ?? new ContentLineWriter(75, true);
    }

    /**
     * Write a component to iCalendar format
     *
     * @param ComponentInterface $component The component to write
     * @return string The serialized component (unfolded)
     */
    public function write(ComponentInterface $component): string
    {
        $lines = [];
        $name = $component->getName();

        // BEGIN marker
        $lines[] = 'BEGIN:' . $name;

        // --- Property Serialization with Conflict Resolution ---
        // Collect all properties first to resolve conflicts before writing.
        // This is particularly important for DESCRIPTION vs STYLED-DESCRIPTION according to RFC 9073.
        $properties = $component->getProperties();
        
        $styledDescPresent = false;
        foreach ($properties as $property) {
            if (strtoupper($property->getName()) === 'STYLED-DESCRIPTION') {
                $styledDescPresent = true;
                break;
            }
        }

        if (!$styledDescPresent) {
            $finalProperties = $properties;
        } else {
            $descriptionIndices = [];
            $finalProperties = [];

            foreach ($properties as $property) {
                $propName = strtoupper($property->getName());
                if ($propName === 'STYLED-DESCRIPTION') {
                    $finalProperties[] = $property;
                } elseif ($propName === 'DESCRIPTION') {
                    $descriptionIndices[] = [
                        'index' => count($finalProperties),
                        'property' => $property
                    ];
                    $finalProperties[] = $property;
                } else {
                    $finalProperties[] = $property;
                }
            }

            if (!empty($descriptionIndices)) {
                $toRemove = [];
                foreach ($descriptionIndices as $info) {
                    $params = $info['property']->getParameters();
                    $derivedParam = $params['DERIVED'] ?? null;
                    if ($derivedParam === null || strtoupper($derivedParam) !== 'TRUE') {
                        $toRemove[] = $info['index'];
                    }
                }

                if (!empty($toRemove)) {
                    foreach (array_reverse($toRemove) as $index) {
                        array_splice($finalProperties, $index, 1);
                    }
                }
            }
        }

        // Write the finalized properties
        foreach ($finalProperties as $property) {
            $lines[] = $this->propertyWriter->write($property);
        }
        // --- End Conflict Resolution ---

        // Serialize sub-components recursively
        $subComponents = $component->getComponents();
        foreach ($subComponents as $subComponent) {
            $lines[] = $this->write($subComponent); // Recursive call to write sub-components
        }

        // END marker
        $lines[] = 'END:' . $name;

        // Join lines with CRLF. Line folding is handled by the ContentLineWriter which is part of the main Writer.
        return implode("\r\n", $lines);
    }

    /**
     * Write multiple components
     *
     * @param ComponentInterface[] $components
     */
    public function writeMultiple(array $components): string
    {
        $output = '';
        foreach ($components as $component) {
            $output .= $this->write($component) . "\r\n";
        }
        return rtrim($output, "\r\n");
    }

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
     * Get the property writer instance
     *
     * Provides access to the underlying PropertyWriter for advanced
     * configuration of property serialization behavior.
     *
     * @return PropertyWriter The property writer instance
     */
    public function getPropertyWriter(): PropertyWriter
    {
        return $this->propertyWriter;
    }

    /**
     * Set the property writer
     */
    public function setPropertyWriter(PropertyWriter $propertyWriter): void
    {
        $this->propertyWriter = $propertyWriter;
    }
}