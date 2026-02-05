<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\VAlarm;
use Icalendar\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VAlarm component
 */
class VAlarmTest extends TestCase
{
    public function testCreateVAlarm(): void
    {
        $alarm = new VAlarm();

        $this->assertEquals('VALARM', $alarm->getName());
    }

    public function testSetAndGetAction(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_DISPLAY);

        $this->assertEquals('DISPLAY', $alarm->getAction());
    }

    public function testSetAndGetTrigger(): void
    {
        $alarm = new VAlarm();
        $alarm->setTrigger('-PT15M');

        $this->assertEquals('-PT15M', $alarm->getTrigger());
    }

    public function testSetAndGetDuration(): void
    {
        $alarm = new VAlarm();
        $alarm->setDuration('PT5M');

        $this->assertEquals('PT5M', $alarm->getDuration());
    }

    public function testSetAndGetRepeat(): void
    {
        $alarm = new VAlarm();
        $alarm->setRepeat(3);

        $this->assertEquals(3, $alarm->getRepeat());
    }

    public function testSetAndGetDescription(): void
    {
        $alarm = new VAlarm();
        $alarm->setDescription('Meeting reminder');

        $this->assertEquals('Meeting reminder', $alarm->getDescription());
    }

    public function testSetAndGetSummary(): void
    {
        $alarm = new VAlarm();
        $alarm->setSummary('Reminder: Team Meeting');

        $this->assertEquals('Reminder: Team Meeting', $alarm->getSummary());
    }

    public function testSetAndGetAttendee(): void
    {
        $alarm = new VAlarm();
        $alarm->setAttendee('mailto:user@example.com');

        $this->assertEquals('mailto:user@example.com', $alarm->getAttendee());
    }

    public function testSetAndGetAttach(): void
    {
        $alarm = new VAlarm();
        $alarm->setAttach('ftp://example.com/sounds/bell.aud');

        $this->assertEquals('ftp://example.com/sounds/bell.aud', $alarm->getAttach());
    }

    // ========== Validation Tests ==========

    public function testValidateRequiresAction(): void
    {
        $alarm = new VAlarm();
        $alarm->setTrigger('-PT15M');

        try {
            $alarm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-ALARM-001', $e->getErrorCode());
            $this->assertStringContainsString('ACTION', $e->getMessage());
        }
    }

    public function testValidateRequiresTrigger(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_DISPLAY);

        try {
            $alarm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-ALARM-002', $e->getErrorCode());
            $this->assertStringContainsString('TRIGGER', $e->getMessage());
        }
    }

    public function testValidateInvalidActionType(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction('INVALID');
        $alarm->setTrigger('-PT15M');

        try {
            $alarm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-ALARM-VAL-002', $e->getErrorCode());
            $this->assertStringContainsString('ACTION', $e->getMessage());
            $this->assertStringContainsString('AUDIO, DISPLAY, or EMAIL', $e->getMessage());
        }
    }

    public function testValidateDisplayRequiresDescription(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_DISPLAY);
        $alarm->setTrigger('-PT15M');

        try {
            $alarm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-ALARM-003', $e->getErrorCode());
            $this->assertStringContainsString('DESCRIPTION', $e->getMessage());
        }
    }

    public function testValidateDisplayWithDescription(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_DISPLAY);
        $alarm->setTrigger('-PT15M');
        $alarm->setDescription('Meeting reminder');

        $this->assertNull($alarm->validate());
    }

    public function testValidateEmailRequiresSummary(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_EMAIL);
        $alarm->setTrigger('-PT15M');
        $alarm->setDescription('Meeting reminder');
        $alarm->setAttendee('mailto:user@example.com');

        try {
            $alarm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-ALARM-004', $e->getErrorCode());
            $this->assertStringContainsString('SUMMARY', $e->getMessage());
        }
    }

    public function testValidateEmailRequiresDescription(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_EMAIL);
        $alarm->setTrigger('-PT15M');
        $alarm->setSummary('Reminder');
        $alarm->setAttendee('mailto:user@example.com');

        try {
            $alarm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-ALARM-004', $e->getErrorCode());
            $this->assertStringContainsString('DESCRIPTION', $e->getMessage());
        }
    }

    public function testValidateEmailRequiresAttendee(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_EMAIL);
        $alarm->setTrigger('-PT15M');
        $alarm->setSummary('Reminder');
        $alarm->setDescription('Meeting reminder');

        try {
            $alarm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-ALARM-004', $e->getErrorCode());
            $this->assertStringContainsString('ATTENDEE', $e->getMessage());
        }
    }

    public function testValidateEmailWithAllProperties(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_EMAIL);
        $alarm->setTrigger('-PT15M');
        $alarm->setSummary('Reminder: Team Meeting');
        $alarm->setDescription('Your meeting starts in 15 minutes');
        $alarm->setAttendee('mailto:user@example.com');

        $this->assertNull($alarm->validate());
    }

    public function testValidateAudioMinimal(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $alarm->setTrigger('-PT15M');

        $this->assertNull($alarm->validate());
    }

    public function testValidateAudioWithAttach(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $alarm->setTrigger('-PT15M');
        $alarm->setAttach('ftp://example.com/sounds/bell.aud');

        $this->assertNull($alarm->validate());
    }

    public function testValidateRepeatWithoutDuration(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $alarm->setTrigger('-PT15M');
        $alarm->setRepeat(3);

        try {
            $alarm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-ALARM-VAL-001', $e->getErrorCode());
            $this->assertStringContainsString('REPEAT', $e->getMessage());
            $this->assertStringContainsString('DURATION', $e->getMessage());
        }
    }

    public function testValidateDurationWithoutRepeat(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $alarm->setTrigger('-PT15M');
        $alarm->setDuration('PT5M');

        try {
            $alarm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-ALARM-VAL-001', $e->getErrorCode());
            $this->assertStringContainsString('REPEAT', $e->getMessage());
            $this->assertStringContainsString('DURATION', $e->getMessage());
        }
    }

    public function testValidateRepeatAndDurationTogether(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $alarm->setTrigger('-PT15M');
        $alarm->setRepeat(3);
        $alarm->setDuration('PT5M');

        $this->assertNull($alarm->validate());
    }

    // ========== Getter When Not Set Tests ==========

    public function testGetActionWhenNotSet(): void
    {
        $alarm = new VAlarm();

        $this->assertNull($alarm->getAction());
    }

    public function testGetTriggerWhenNotSet(): void
    {
        $alarm = new VAlarm();

        $this->assertNull($alarm->getTrigger());
    }

    public function testGetDurationWhenNotSet(): void
    {
        $alarm = new VAlarm();

        $this->assertNull($alarm->getDuration());
    }

    public function testGetRepeatWhenNotSet(): void
    {
        $alarm = new VAlarm();

        $this->assertNull($alarm->getRepeat());
    }

    public function testGetDescriptionWhenNotSet(): void
    {
        $alarm = new VAlarm();

        $this->assertNull($alarm->getDescription());
    }

    public function testGetSummaryWhenNotSet(): void
    {
        $alarm = new VAlarm();

        $this->assertNull($alarm->getSummary());
    }

    public function testGetAttendeeWhenNotSet(): void
    {
        $alarm = new VAlarm();

        $this->assertNull($alarm->getAttendee());
    }

    public function testGetAttachWhenNotSet(): void
    {
        $alarm = new VAlarm();

        $this->assertNull($alarm->getAttach());
    }

    // ========== Fluent Interface Tests ==========

    public function testFluentInterface(): void
    {
        $alarm = new VAlarm();

        $result = $alarm->setAction(VAlarm::ACTION_DISPLAY)
            ->setTrigger('-PT15M')
            ->setDescription('Meeting reminder');

        $this->assertSame($alarm, $result);
        $this->assertEquals('DISPLAY', $alarm->getAction());
        $this->assertEquals('-PT15M', $alarm->getTrigger());
        $this->assertEquals('Meeting reminder', $alarm->getDescription());
    }

    public function testFluentInterfaceEmail(): void
    {
        $alarm = new VAlarm();

        $result = $alarm->setAction(VAlarm::ACTION_EMAIL)
            ->setTrigger('-PT30M')
            ->setSummary('Reminder')
            ->setDescription('Your event starts soon')
            ->setAttendee('mailto:user@example.com');

        $this->assertSame($alarm, $result);
        $this->assertNull($alarm->validate());
    }

    public function testFluentInterfaceAudioWithRepeat(): void
    {
        $alarm = new VAlarm();

        $result = $alarm->setAction(VAlarm::ACTION_AUDIO)
            ->setTrigger('-PT15M')
            ->setAttach('ftp://example.com/sounds/bell.aud')
            ->setRepeat(3)
            ->setDuration('PT5M');

        $this->assertSame($alarm, $result);
        $this->assertNull($alarm->validate());
    }

    // ========== Action Type Tests ==========

    public function testAllActionTypes(): void
    {
        $alarm = new VAlarm();

        // AUDIO
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $this->assertEquals('AUDIO', $alarm->getAction());

        // DISPLAY
        $alarm->setAction(VAlarm::ACTION_DISPLAY);
        $this->assertEquals('DISPLAY', $alarm->getAction());

        // EMAIL
        $alarm->setAction(VAlarm::ACTION_EMAIL);
        $this->assertEquals('EMAIL', $alarm->getAction());
    }

    // ========== Trigger Format Tests ==========

    public function testTriggerRelativeBefore(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $alarm->setTrigger('-PT15M');

        $this->assertEquals('-PT15M', $alarm->getTrigger());
        $this->assertNull($alarm->validate());
    }

    public function testTriggerRelativeAfter(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $alarm->setTrigger('PT15M');

        $this->assertEquals('PT15M', $alarm->getTrigger());
        $this->assertNull($alarm->validate());
    }

    public function testTriggerAbsolute(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $alarm->setTrigger('20240215T120000Z');

        $this->assertEquals('20240215T120000Z', $alarm->getTrigger());
        $this->assertNull($alarm->validate());
    }

    // ========== Overwrite Tests ==========

    public function testOverwriteAction(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction(VAlarm::ACTION_AUDIO);
        $alarm->setAction(VAlarm::ACTION_DISPLAY);

        $this->assertEquals('DISPLAY', $alarm->getAction());
    }

    public function testOverwriteTrigger(): void
    {
        $alarm = new VAlarm();
        $alarm->setTrigger('-PT15M');
        $alarm->setTrigger('-PT30M');

        $this->assertEquals('-PT30M', $alarm->getTrigger());
    }
}
