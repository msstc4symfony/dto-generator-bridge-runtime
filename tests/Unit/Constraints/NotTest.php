<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\IfThenElse;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Not;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\NotValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Present;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Payment;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Voucher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotTest extends TestCase
{
    use Validates;

    public function testRejectsAValueThatPassesTheConstraints(): void
    {
        $constraint = new Not([new Regex('/^\+/'), new Length(null, 3)]);

        $violations = $this->validator()->validate('+123', $constraint);
        self::assertSame(['This value is not allowed.'], $this->messages($violations));
        self::assertSame(Not::NOT_ALLOWED_ERROR, $violations->get(0)->getCode());
        self::assertSame('+123', $violations->get(0)->getInvalidValue());
        self::assertSame([], $this->violations('+1', $constraint), 'One failing constraint is enough.');
        self::assertSame([], $this->violations('123', $constraint));
    }

    public function testRejectsEveryValueWithoutConstraints(): void
    {
        self::assertSame(['This value is not allowed.'], $this->violations('a', new Not()));
        self::assertSame(['Default'], (new Not())->groups);
    }

    public function testTakesAMessage(): void
    {
        self::assertSame(['Not this one.'], $this->violations('a', new Not([], 'Not this one.')));
    }

    public function testChecksTheObjectOfAClassConstraint(): void
    {
        $constraint = new Not([new Present(['card', 'holder'])]);

        self::assertSame(['This value is not allowed.'], $this->violations(new Payment('4242', 'Ann', null), $constraint));
        self::assertSame([], $this->violations(new Payment('4242', null, null), $constraint));
    }

    public function testReportsInTheContextWhenItHoldsAnotherNot(): void
    {
        $constraint = new Not([new Not([new IdenticalTo('a')])]);

        self::assertSame(['This value is not allowed.'], $this->violations('b', $constraint));
        self::assertSame([], $this->violations('a', $constraint));
        self::assertSame([], $this->violations('b', new IfThenElse([new Not([new IdenticalTo('a')])], [new Length(null, 1)])));
    }

    public function testSkipsNull(): void
    {
        self::assertSame([], $this->violations(null, new Not()));
    }

    public function testRejectsNestedConstraintsOfOtherGroups(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The constraints inside Not must be in all of its groups, or it would match on part of them.');

        new Not([new IdenticalTo('a', null, null, ['a']), new IdenticalTo('b', null, null, ['b'])]);
    }

    public function testRejectsNestedConstraintsOutsideItsGroups(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The constraints inside Not must be in all of its groups, or it would match on part of them.');

        new Not([new IdenticalTo('b', null, null, ['b'])], null, ['a', 'b']);
    }

    public function testTakesTheGroupsOfItsConstraints(): void
    {
        $constraint = new Not([new IdenticalTo('a', null, null, ['shipping'])]);

        self::assertSame(['shipping'], $constraint->groups);
        self::assertSame([], $this->violations('a', $constraint));
        self::assertCount(1, $this->validator()->validate('a', $constraint, ['shipping']));
    }

    public function testChecksItsConstraintsInTheGroupBeingValidated(): void
    {
        $constraint = new Not([new IdenticalTo('a')], null, ['shipping']);

        self::assertCount(0, $this->validator()->validate('b', $constraint, ['shipping']));
        self::assertCount(1, $this->validator()->validate('a', $constraint, ['shipping']));
    }

    public function testRejectsAnotherConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new NotValidator())->validate('a', new NotNull());
    }

    public function testIsAClassAndAPropertyConstraint(): void
    {
        self::assertSame([Constraint::PROPERTY_CONSTRAINT, Constraint::CLASS_CONSTRAINT], (new Not())->getTargets());
    }

    /**
     * @requires PHP 8.1
     */
    public function testIsReadFromTheClassAndPropertyAttributes(): void
    {
        $validator = $this->mappedValidator();

        self::assertSame(['This value is not allowed.', 'code: This value is not allowed.'], $this->messages($this->validateMapped($validator, new Voucher('TEST', '4242'))));
        self::assertSame([], $this->messages($this->validateMapped($validator, new Voucher('SPRING', null))));
    }
}
