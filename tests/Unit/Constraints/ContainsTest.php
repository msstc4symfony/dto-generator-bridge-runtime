<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use ArrayIterator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Contains;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\ContainsValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Catalogue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ContainsTest extends TestCase
{
    use Validates;

    public function testAcceptsAListWithAMatchingElement(): void
    {
        self::assertSame([], $this->violations(['user', 'admin'], new Contains([new IdenticalTo('admin')])));
    }

    public function testReportsTooFewMatchingElementsOnTheListAlone(): void
    {
        $violations = $this->validator()->validate(['user', 'guest'], new Contains([new IdenticalTo('admin')]));

        self::assertSame(['This collection should contain 1 matching element or more.'], $this->messages($violations));
        $violation = $violations->get(0);
        self::assertSame(Contains::TOO_FEW_ERROR, $violation->getCode());
        self::assertSame(['user', 'guest'], $violation->getInvalidValue());
        self::assertSame(['{{ count }}' => '0', '{{ limit }}' => '1'], $violation->getParameters());
        self::assertSame(1, $violation->getPlural());
    }

    public function testCountsAgainstTheMinimum(): void
    {
        $constraint = new Contains([new Regex('/^a/')], 2);

        self::assertSame(['This collection should contain 2 matching elements or more.'], $this->violations(['ab', 'b'], $constraint));
        self::assertSame([], $this->violations(['ab', 'b', 'ac'], $constraint));
    }

    public function testCountsAgainstTheMaximum(): void
    {
        $constraint = new Contains([new Regex('/^a/')], 1, 2);

        self::assertSame([], $this->violations(['ab', 'ac'], $constraint));
        $violations = $this->validator()->validate(['ab', 'ac', 'ad'], $constraint);
        self::assertSame(['This collection should contain 2 matching elements or less.'], $this->messages($violations));
        $violation = $violations->get(0);
        self::assertSame(Contains::TOO_MANY_ERROR, $violation->getCode());
        self::assertSame(['{{ count }}' => '3', '{{ limit }}' => '2'], $violation->getParameters());
        self::assertSame(2, $violation->getPlural());
    }

    public function testAllowsNoMatchWithAMinimumOfZero(): void
    {
        self::assertSame([], $this->violations([], new Contains([new Regex('/^a/')], 0)));
        self::assertSame(['This collection should contain 0 matching elements or less.'], $this->violations(['a'], new Contains([new Regex('/^a/')], 0, 0)));
    }

    public function testMatchesEveryElementWithoutConstraints(): void
    {
        self::assertSame(['This collection should contain 1 matching element or more.'], $this->violations([], new Contains()));
        self::assertSame([], $this->violations([null], new Contains()));
    }

    public function testTakesTheMessages(): void
    {
        self::assertSame(['Too few (0 of 1).'], $this->violations([], new Contains([], 1, null, 'Too few ({{ count }} of {{ limit }}).')));
        self::assertSame(['Too many (1 of 0).'], $this->violations(['a'], new Contains([], 0, 0, null, 'Too many ({{ count }} of {{ limit }}).')));
    }

    public function testReportsATraversable(): void
    {
        self::assertSame(['This value should be of type array.'], $this->violations(new ArrayIterator(['admin']), new Contains([new IdenticalTo('admin')])));
    }

    public function testReportsOnTheListWhenItsElementsHoldContains(): void
    {
        $constraint = new Contains([new Contains([new IdenticalTo(1)])]);

        self::assertSame(['This collection should contain 1 matching element or more.'], $this->violations([[2], [3]], $constraint));
        self::assertSame([], $this->violations([[2], [1]], $constraint));
    }

    public function testRejectsNestedConstraintsOfOtherGroups(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The constraints inside Contains must be in all of its groups, or an element would match on part of them.');

        new Contains([new IdenticalTo('admin', null, null, ['a']), new IdenticalTo('owner', null, null, ['b'])], 1, null, null, null, ['a', 'b']);
    }

    public function testChecksTheElementsInTheGroupBeingValidated(): void
    {
        $constraint = new Contains([new IdenticalTo('admin')], 1, null, null, null, ['roles']);

        self::assertSame([], $this->violations(['user'], $constraint));
        self::assertCount(1, $this->validator()->validate(['user'], $constraint, ['roles']));
    }

    public function testSkipsNull(): void
    {
        self::assertSame([], $this->violations(null, new Contains([new IdenticalTo('admin')])));
    }

    public function testReportsAValueThatIsNotAnArray(): void
    {
        self::assertSame(['This value should be of type array.'], $this->violations('admin', new Contains([new IdenticalTo('admin')])));
    }

    public function testRejectsANegativeMinimum(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The minimum of Contains must be 0 or more, and its maximum not below it; got -1 and none.');

        new Contains([], -1);
    }

    public function testRejectsAMaximumBelowTheMinimum(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The minimum of Contains must be 0 or more, and its maximum not below it; got 2 and 1.');

        new Contains([], 2, 1);
    }

    public function testAcceptsAMaximumEqualToTheMinimum(): void
    {
        self::assertSame(2, (new Contains([], 2, 2))->max);
    }

    public function testRejectsAnotherConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new ContainsValidator())->validate([], new NotNull());
    }

    public function testNamesItsErrors(): void
    {
        $this->requireErrorNames();

        self::assertSame('TOO_FEW_ERROR', Contains::getErrorName(Contains::TOO_FEW_ERROR));
        self::assertSame('TOO_MANY_ERROR', Contains::getErrorName(Contains::TOO_MANY_ERROR));
    }

    /**
     * @requires PHP 8.1
     */
    public function testIsReadFromThePropertyAttribute(): void
    {
        self::assertSame(
            ['roles: This collection should contain 1 matching element or more.'],
            $this->messages($this->mappedValidator()->validate(new Catalogue([], ['user']))),
        );
    }
}
