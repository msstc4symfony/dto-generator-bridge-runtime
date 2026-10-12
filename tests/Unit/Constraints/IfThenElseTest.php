<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\IfThenElse;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\IfThenElseValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Present;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Property;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Payment;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Shipment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class IfThenElseTest extends TestCase
{
    use Validates;

    public function testChecksThenWhenTheValueMatchesIf(): void
    {
        $constraint = new IfThenElse([new Regex('/^\+/')], [new Length(null, 8)], [new Length(null, null, 6)]);

        self::assertSame(['This value is too short. It should have 8 characters or more.'], $this->violations('+123', $constraint));
        self::assertSame([], $this->violations('+12345678', $constraint));
    }

    public function testChecksElseWhenTheValueFailsIf(): void
    {
        $constraint = new IfThenElse([new Regex('/^\+/')], [new Length(null, 8)], [new Length(null, null, 6)]);

        self::assertSame(['This value is too long. It should have 6 characters or less.'], $this->violations('1234567', $constraint));
        self::assertSame([], $this->violations('123', $constraint));
    }

    public function testMatchesEveryValueWithoutIf(): void
    {
        self::assertSame(['This value should be identical to string "a".'], $this->violations('b', new IfThenElse([], [new IdenticalTo('a')], [new NotNull()])));
    }

    public function testChecksNothingWithoutABranch(): void
    {
        self::assertSame([], $this->violations('b', new IfThenElse([new IdenticalTo('a')])));
        self::assertSame(['Default'], (new IfThenElse())->groups);
    }

    public function testChecksTheObjectOfAClassConstraint(): void
    {
        $constraint = new IfThenElse(
            [new Property('card', [new IdenticalTo('4242')])],
            [new Present(['holder']), new Property('expiry', [new Regex('/^\d\d\/\d\d$/')])],
            [new Present(['expiry'])],
        );

        self::assertSame(['holder: This field is missing.', 'expiry: This value is not valid.'], $this->violations(new Payment('4242', null, '2030'), $constraint));
        self::assertSame(['expiry: This field is missing.'], $this->violations(new Payment('1111', null, null), $constraint));
        self::assertSame([], $this->violations(new Payment(null, 'Ann', null), $constraint), 'A missing property passes its constraints, as with "properties".');
    }

    public function testReportsInTheContextWhenIfHoldsIfThenElse(): void
    {
        $constraint = new IfThenElse([new IfThenElse([new IdenticalTo('a')], [new Length(null, 2)])], [new IdenticalTo('z')]);

        self::assertSame(['This value should be identical to string "z".'], $this->violations('b', $constraint));
        self::assertSame([], $this->violations('a', $constraint));
    }

    public function testSkipsNull(): void
    {
        self::assertSame([], $this->violations(null, new IfThenElse([], [new NotNull()])));
    }

    public function testRejectsNestedConstraintsOfOtherGroups(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The constraints inside IfThenElse must be in all of its groups, or "if" would match on part of them.');

        new IfThenElse([new IdenticalTo('a', null, null, ['a'])], [new IdenticalTo('b', null, null, ['b'])]);
    }

    public function testRejectsNestedConstraintsOutsideItsGroups(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The constraints inside IfThenElse must be in all of its groups, or "if" would match on part of them.');

        new IfThenElse([], [], [new IdenticalTo('b', null, null, ['b'])], ['a', 'b']);
    }

    public function testTakesTheGroupsOfItsConstraints(): void
    {
        $constraint = new IfThenElse([new IdenticalTo('a', null, null, ['shipping'])], [], [new IdenticalTo('b', null, null, ['shipping'])]);

        self::assertSame(['shipping'], $constraint->groups);
        self::assertSame([], $this->violations('c', $constraint));
        self::assertCount(1, $this->validator()->validate('c', $constraint, ['shipping']));
    }

    public function testChecksIfInTheGroupBeingValidated(): void
    {
        $constraint = new IfThenElse([new IdenticalTo('a')], [new Length(null, 2)], [], ['shipping']);

        self::assertCount(0, $this->validator()->validate('b', $constraint, ['shipping']));
        self::assertCount(1, $this->validator()->validate('a', $constraint, ['shipping']));
    }

    public function testRejectsAnotherConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new IfThenElseValidator())->validate('a', new NotNull());
    }

    public function testIsAClassAndAPropertyConstraint(): void
    {
        self::assertSame([Constraint::PROPERTY_CONSTRAINT, Constraint::CLASS_CONSTRAINT], (new IfThenElse())->getTargets());
    }

    /**
     * @requires PHP 8.1
     */
    public function testIsReadFromTheClassAndPropertyAttributes(): void
    {
        $validator = $this->mappedValidator();

        self::assertSame(
            ['zip: This field is missing.', 'phone: This value is too short. It should have 8 characters or more.'],
            $this->messages($this->validateMapped($validator, new Shipment('US', null, '+1'))),
        );
        self::assertSame(
            ['zip: This value is too long. It should have 10 characters or less.', 'phone: This value is too long. It should have 6 characters or less.'],
            $this->messages($this->validateMapped($validator, new Shipment('DE', '12345678901', '1234567'))),
        );
    }
}
