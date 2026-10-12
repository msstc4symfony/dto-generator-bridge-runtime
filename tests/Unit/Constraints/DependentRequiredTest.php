<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\DependentRequired;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\DependentRequiredValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Payment;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Refund;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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

    public function testReadsAProtectedPropertyOfTheParent(): void
    {
        $constraint = new DependentRequired(['currency' => ['card']]);

        self::assertSame(['card: This value should not be null when "currency" is set.'], $this->violations(new Payment(null, null, null, null, 'EUR'), $constraint));
        self::assertSame([], $this->violations(new Payment(null, null, null), $constraint));
    }

    public function testReadsThePrivatePropertyOfTheClassBeforeTheParentsOfTheSameName(): void
    {
        $constraint = new DependentRequired(['amount' => ['reason']]);

        self::assertSame(['reason: This value should not be null when "amount" is set.'], $this->violations(new Refund(5, null, null), $constraint));
        self::assertSame([], $this->violations(new Refund(null, 5, null), $constraint));
    }

    public function testTakesAnUninitializedPropertyAsNotSet(): void
    {
        $payment = (new ReflectionClass(Payment::class))->newInstanceWithoutConstructor();
        self::assertSame([], $this->violations($payment, new DependentRequired(['card' => ['holder'], 'amount' => ['holder']])));

        $payment->card = '4242';
        self::assertSame(['holder: This value should not be null when "card" is set.'], $this->violations($payment, new DependentRequired(['card' => ['holder']])));
    }

    public function testReportsAMissingPropertyOnceForSeveralThatNeedIt(): void
    {
        self::assertSame(
            ['holder: This value should not be null when "card" is set.'],
            $this->violations(new Payment('4242', null, '12/30'), new DependentRequired(['card' => ['holder'], 'expiry' => ['holder']])),
        );
    }

    /**
     * @dataProvider malformedRequirements
     *
     * @param array<array-key, mixed> $requirements
     */
    public function testRejectsMalformedRequirements(array $requirements): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('DependentRequired maps PHP property names to lists of the names they need.');

        new DependentRequired($requirements);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>}>
     */
    public static function malformedRequirements(): iterable
    {
        yield 'a list' => [['card', 'holder']];
        yield 'a name instead of a list' => [['card' => 'holder']];
        yield 'a map of names' => [['card' => ['a' => 'holder']]];
        yield 'a number among the names' => [['card' => ['holder', 1]]];
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
        $violations = $this->validateMapped($this->mappedValidator(), new Payment('4242', 'Ann', null));

        self::assertSame(['expiry: This value should not be null when "card" is set.'], array_map(
            static fn (ConstraintViolationInterface $violation): string => $violation->getPropertyPath() . ': ' . $violation->getMessage(),
            iterator_to_array($violations),
        ));
    }
}
