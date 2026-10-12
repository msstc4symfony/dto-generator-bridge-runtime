<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\DependentRequired;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\DependentRequiredValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Payment;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class DependentRequiredTest extends TestCase
{
    use Validates;

    public function testAcceptsAnObjectWithoutTheTrigger(): void
    {
        self::assertSame([], $this->violations(new Payment(null, null, null), new DependentRequired(['card' => ['holder', 'expiry']])));
    }

    public function testAcceptsAnObjectWithTheDependents(): void
    {
        self::assertSame([], $this->violations(new Payment('4242', 'Ann', '12/30'), new DependentRequired(['card' => ['holder', 'expiry']])));
    }

    public function testReportsEachMissingDependentAtItsPath(): void
    {
        $violations = $this->validator()->validate(new Payment('4242', null, null), new DependentRequired(['card' => ['holder', 'expiry']]));

        self::assertSame([
            'holder: This value should not be null when "card" is set.',
            'expiry: This value should not be null when "card" is set.',
        ], $this->messages($violations));
        $first = $violations->get(0);
        self::assertSame(DependentRequired::MISSING_ERROR, $first->getCode());
        self::assertNull($first->getInvalidValue());
        self::assertSame(['{{ property }}' => '"card"'], $first->getParameters());
    }

    public function testCountsEmptyValuesAsSet(): void
    {
        self::assertSame(
            ['holder: This value should not be null when "card" is set.'],
            $this->violations(new Payment('', null, ''), new DependentRequired(['card' => ['holder', 'expiry']])),
        );
    }

    public function testReadsThePrivatePropertiesOfTheParent(): void
    {
        $constraint = new DependentRequired(['amount' => ['card'], 'card' => ['amount']]);

        self::assertSame(['amount: This value should not be null when "card" is set.'], $this->violations(new Payment('4242', null, null), $constraint));
        self::assertSame(['card: This value should not be null when "amount" is set.'], $this->violations(new Payment(null, null, null, 5), $constraint));
    }

    public function testTakesTheMessage(): void
    {
        self::assertSame(
            ['holder: Needs "card".'],
            $this->violations(new Payment('4242', null, '12/30'), new DependentRequired(['card' => ['holder']], 'Needs {{ property }}.')),
        );
    }

    public function testChecksOnlyInItsGroups(): void
    {
        $constraint = new DependentRequired(['card' => ['holder']], null, ['payment']);

        self::assertSame([], $this->violations(new Payment('4242', null, null), $constraint));
        self::assertCount(1, $this->validator()->validate(new Payment('4242', null, null), $constraint, ['payment']));
    }

    public function testSkipsNull(): void
    {
        self::assertSame([], $this->violations(null, new DependentRequired(['card' => ['holder']])));
    }

    public function testReportsAValueThatIsNotAnObject(): void
    {
        self::assertSame(['This value should be of type object.'], $this->violations(['card' => '4242'], new DependentRequired(['card' => ['holder']])));
    }

    public function testRejectsAPropertyTheClassLacks(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The class "stdClass" has no property "card".');

        $object = new stdClass();
        $object->discard = '4242';
        $this->violations($object, new DependentRequired(['card' => ['holder']]));
    }

    public function testRejectsAnotherConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new DependentRequiredValidator())->validate(new Payment(null, null, null), new NotNull());
    }

    public function testIsAClassConstraint(): void
    {
        self::assertSame(Constraint::CLASS_CONSTRAINT, (new DependentRequired([]))->getTargets());
    }

    public function testNamesItsError(): void
    {
        $this->requireErrorNames();

        self::assertSame('MISSING_ERROR', DependentRequired::getErrorName(DependentRequired::MISSING_ERROR));
    }

    /**
     * @requires PHP 8.0
     */
    public function testIsReadFromTheClassAttribute(): void
    {
        $violations = $this->mappedValidator()->validate(new Payment('4242', 'Ann', null));

        self::assertSame(['expiry: This value should not be null when "card" is set.'], array_map(
            static fn (ConstraintViolationInterface $violation): string => $violation->getPropertyPath() . ': ' . $violation->getMessage(),
            iterator_to_array($violations),
        ));
    }
}
