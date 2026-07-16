<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\Available;
use Icalendar\Component\ComponentInterface;
use Icalendar\Component\Daylight;
use Icalendar\Component\GenericComponent;
use Icalendar\Component\Participant;
use Icalendar\Component\Standard;
use Icalendar\Component\VAlarm;
use Icalendar\Component\VAvailability;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VFreeBusy;
use Icalendar\Component\VJournal;
use Icalendar\Component\VTimezone;
use Icalendar\Component\VTodo;
use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * validate() must be expressible on the ComponentInterface type.
 *
 * Every concrete component implemented validate(), but it was declared on
 * neither ComponentInterface nor AbstractComponent, and GenericComponent did not
 * implement it at all. So no type could express "a validatable component":
 * generic code over ComponentInterface could not call validate() without both
 * PHPStan (level 9) and Psalm rejecting it --
 *
 *   UndefinedInterfaceMethod - Method ComponentInterface::validate does not exist
 *
 * -- which forced consumers into a method_exists() guard before every call.
 *
 * This covers the declaration only. It deliberately does not make validate()
 * recurse into children: component validate() throws on the first error, so
 * recursing it would report one problem per run, and the Validator class already
 * walks the tree and collects every error. See ISSUES.md #2.
 */
class ValidateContractTest extends TestCase
{
    public function testValidateIsDeclaredOnTheInterface(): void
    {
        $this->assertTrue(
            method_exists(ComponentInterface::class, 'validate'),
            'ComponentInterface must declare validate() so generic code can call it'
        );
    }

    /** The whole point: no method_exists() guard needed. */
    public function testValidateIsCallableThroughTheInterfaceType(): void
    {
        $component = $this->interfaceTyped(new VCalendar());
        $component->addProperty(GenericProperty::create('PRODID', '-//test//test//EN'));
        $component->addProperty(GenericProperty::create('VERSION', '2.0'));

        $this->assertNull($component->validate());
    }

    /** GenericComponent had no validate() at all; it must inherit the default. */
    public function testGenericComponentValidates(): void
    {
        $this->assertNull($this->interfaceTyped(new GenericComponent('X-CUSTOM'))->validate());
    }

    /** @return array<string, array{ComponentInterface}> */
    public static function componentProvider(): array
    {
        return [
            'VCalendar' => [new VCalendar()],
            'VEvent' => [new VEvent()],
            'VTodo' => [new VTodo()],
            'VJournal' => [new VJournal()],
            'VFreeBusy' => [new VFreeBusy()],
            'VTimezone' => [new VTimezone()],
            'VAlarm' => [new VAlarm()],
            'VAvailability' => [new VAvailability()],
            'Available' => [new Available()],
            'Standard' => [new Standard()],
            'Daylight' => [new Daylight()],
            'Participant' => [new Participant()],
            'GenericComponent' => [new GenericComponent('X-CUSTOM')],
        ];
    }

    /**
     * Every component must satisfy the contract: validate() either returns void
     * or throws ValidationException. No fatals, no undefined methods.
     */
    #[DataProvider('componentProvider')]
    public function testEveryComponentHonoursTheContract(ComponentInterface $component): void
    {
        try {
            $this->assertNull($component->validate());
        } catch (ValidationException $e) {
            // An empty component failing validation is fine; it must not fatal.
            $this->assertNotSame('', $e->getMessage());
        }
    }

    /** The no-op default must not weaken existing validation. */
    public function testConcreteValidationStillThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->interfaceTyped(new VCalendar())->validate(); // no PRODID/VERSION
    }

    /** Forces the call site to be typed as the interface, not the concrete class. */
    private function interfaceTyped(ComponentInterface $component): ComponentInterface
    {
        return $component;
    }
}
