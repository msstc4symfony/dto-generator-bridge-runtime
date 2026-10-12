<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Property;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\PropertyValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Payment;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class PropertyTest extends TestCase
{
    use Validates;

    public function testChecksTheValueAtItsPath(): void
    {
        $violations = $this->validator()->validate(new Payment('4242', 'Ann', '12/30'), new Property('holder', [new IdenticalTo('Bob'), new Length(null, 4)]));

        self::assertSame([
            'holder: This value should be identical to string "Bob".',
            'holder: This value is too short. It should have 4 characters or more.',
        ], $this->messages($violations));
        self::assertSame('Ann', $violations->get(0)->getInvalidValue());
    }

    public function testAcceptsAMatchingValue(): void
    {
        self::assertSame([], $this->violations(new Payment('4242', 'Bob', null), new Property('holder', [new IdenticalTo('Bob')])));
    }

    public function testSkipsAMissingProperty(): void
    {
        self::assertSame([], $this->violations(new Payment('4242', null, null), new Property('holder', [new NotNull()])));

        $payment = (new ReflectionClass(Payment::class))->newInstanceWithoutConstructor();
        self::assertSame([], $this->violations($payment, new Property('holder', [new NotNull()])));
    }

    public function testReadsTheProtectedPropertyOfTheParent(): void
    {
        self::assertSame(
            ['currency: This value should be identical to string "USD".'],
            $this->violations(new Payment(null, null, null, null, 'EUR'), new Property('currency', [new IdenticalTo('USD')])),
        );
    }

    public function testChecksBelowThePathOfTheObject(): void
    {
        $violations = $this->validator()->startContext()->atPath('payment')->validate(new Payment('4242', 'Ann', null), new Property('holder', [new IdenticalTo('Bob')]))->getViolations();

        self::assertSame(['payment.holder: This value should be identical to string "Bob".'], $this->messages($violations));
    }

    public function testChecksOnlyInItsGroups(): void
    {
        $constraint = new Property('holder', [new IdenticalTo('Bob')], ['payment']);

        self::assertSame([], $this->violations(new Payment('4242', 'Ann', null), $constraint));
        self::assertCount(1, $this->validator()->validate(new Payment('4242', 'Ann', null), $constraint, ['payment']));
    }

    public function testSkipsNull(): void
    {
        self::assertSame([], $this->violations(null, new Property('holder', [new NotNull()])));
    }

    public function testReportsAValueThatIsNotAnObject(): void
    {
        self::assertSame(['This value should be of type object.'], $this->violations(['holder' => 'Ann'], new Property('holder', [new NotNull()])));
    }

    public function testRejectsAPropertyTheClassLacks(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The class "stdClass" has no property "holder".');

        $this->violations(new stdClass(), new Property('holder', [new NotNull()]));
    }

    public function testRejectsAnotherConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new PropertyValidator())->validate(new Payment(null, null, null), new NotNull());
    }

    public function testIsAClassConstraint(): void
    {
        self::assertSame(Constraint::CLASS_CONSTRAINT, (new Property('holder'))->getTargets());
    }
}
