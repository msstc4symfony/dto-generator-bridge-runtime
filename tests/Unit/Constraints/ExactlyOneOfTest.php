<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\ExactlyOneOf;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\ExactlyOneOfValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Not;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Present;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Discount;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Payment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ExactlyOneOfTest extends TestCase
{
    use Validates;

    public function testAcceptsAValueThatPassesExactlyOneConstraint(): void
    {
        $constraint = new ExactlyOneOf([new Regex('/^[0-9]+$/'), new Length(null, null, 3)]);

        self::assertSame([], $this->violations('1234', $constraint));
        self::assertSame([], $this->violations('abc', $constraint));
        $violations = $this->validator()->validate('12', $constraint);
        self::assertSame(['This value should satisfy exactly one of the given constraints; it satisfies 2.'], $this->messages($violations));
        self::assertSame(ExactlyOneOf::NOT_EXACTLY_ONE_ERROR, $violations->get(0)->getCode());
        self::assertSame('12', $violations->get(0)->getInvalidValue());
        self::assertSame(['{{ value }}' => '"12"', '{{ count }}' => '2'], $violations->get(0)->getParameters());
        self::assertSame(['This value should satisfy exactly one of the given constraints; it satisfies 0.'], $this->violations('abcd', $constraint));
    }

    public function testRejectsEveryValueWithoutConstraints(): void
    {
        self::assertSame(['This value should satisfy exactly one of the given constraints; it satisfies 0.'], $this->violations('a', new ExactlyOneOf()));
        self::assertSame(['Default'], (new ExactlyOneOf())->groups);
    }

    public function testTakesAMessage(): void
    {
        self::assertSame(['Pick one, not 0.'], $this->violations('a', new ExactlyOneOf([], 'Pick one, not {{ count }}.')));
    }

    public function testChecksTheObjectOfAClassConstraint(): void
    {
        $constraint = new ExactlyOneOf([new Present(['card']), new Present(['holder'])]);

        self::assertSame([], $this->violations(new Payment('4242', null, null), $constraint));
        self::assertSame(['This value should satisfy exactly one of the given constraints; it satisfies 2.'], $this->violations(new Payment('4242', 'Ann', null), $constraint));
    }

    public function testCountsInTheContextWhenItHoldsAnotherComposite(): void
    {
        $constraint = new ExactlyOneOf([new Not([new IdenticalTo('a')]), new ExactlyOneOf([new IdenticalTo('b')])]);

        self::assertSame([], $this->violations('c', $constraint));
        self::assertSame(['This value should satisfy exactly one of the given constraints; it satisfies 2.'], $this->violations('b', $constraint));
    }

    public function testSkipsNull(): void
    {
        self::assertSame([], $this->violations(null, new ExactlyOneOf()));
    }

    public function testRejectsNestedConstraintsOutsideItsGroups(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The constraints inside ExactlyOneOf must be in all of its groups, or it would count part of them.');

        new ExactlyOneOf([new IdenticalTo('a', null, null, ['a']), new IdenticalTo('b', null, null, ['b'])]);
    }

    public function testTakesTheGroupsOfItsConstraints(): void
    {
        $constraint = new ExactlyOneOf([new IdenticalTo('a', null, null, ['shipping'])]);

        self::assertSame(['shipping'], $constraint->groups);
        self::assertSame([], $this->violations('b', $constraint));
        self::assertCount(1, $this->validator()->validate('b', $constraint, ['shipping']));
        self::assertCount(0, $this->validator()->validate('a', $constraint, ['shipping']));
    }

    public function testChecksItsConstraintsInTheGroupBeingValidated(): void
    {
        $constraint = new ExactlyOneOf([new IdenticalTo('a')], null, ['shipping']);

        self::assertCount(0, $this->validator()->validate('a', $constraint, ['shipping']));
        self::assertCount(1, $this->validator()->validate('b', $constraint, ['shipping']));
    }

    public function testRejectsAnotherConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new ExactlyOneOfValidator())->validate('a', new NotNull());
    }

    public function testIsAClassAndAPropertyConstraint(): void
    {
        self::assertSame([Constraint::PROPERTY_CONSTRAINT, Constraint::CLASS_CONSTRAINT], (new ExactlyOneOf())->getTargets());
    }

    /**
     * @requires PHP 8.1
     */
    public function testIsReadFromThePropertyAttribute(): void
    {
        $validator = $this->mappedValidator();

        self::assertSame(['amount: This value should satisfy exactly one of the given constraints; it satisfies 2.'], $this->messages($this->validateMapped($validator, new Discount('10%'))));
        self::assertSame([], $this->messages($this->validateMapped($validator, new Discount('100%'))));
    }
}
