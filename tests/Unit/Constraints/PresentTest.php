<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Present;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\PresentValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Override;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Payment;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class PresentTest extends TestCase
{
    use Validates;

    public function testReportsEachMissingPropertyAtItsPath(): void
    {
        $violations = $this->validator()->validate(new Payment('4242', null, null), new Present(['card', 'holder', 'expiry']));

        self::assertSame(['holder: This field is missing.', 'expiry: This field is missing.'], $this->messages($violations));
        $first = $violations->get(0);
        self::assertSame(Present::MISSING_ERROR, $first->getCode());
        self::assertNull($first->getInvalidValue());
        self::assertSame(['{{ field }}' => '"holder"'], $first->getParameters());
    }

    public function testCountsEmptyValuesAsPresent(): void
    {
        self::assertSame([], $this->violations(new Payment('', '', ''), new Present(['card', 'holder', 'expiry'])));
    }

    public function testTakesAnUninitializedPropertyAsMissing(): void
    {
        $payment = (new ReflectionClass(Payment::class))->newInstanceWithoutConstructor();

        self::assertSame(['amount: This field is missing.'], $this->violations($payment, new Present(['amount'])));
    }

    public function testReadsThePublicPropertyOfTheClassBeforeAPrivateOneOfTheParent(): void
    {
        self::assertSame(['amount: This field is missing.'], $this->violations(new Override(null, 5), new Present(['amount'])));
        self::assertSame([], $this->violations(new Override(5, null), new Present(['amount'])));
    }

    public function testTakesTheMessage(): void
    {
        self::assertSame(['holder: Give "holder".'], $this->violations(new Payment(null, null, null), new Present(['holder'], 'Give {{ field }}.')));
    }

    public function testChecksOnlyInItsGroups(): void
    {
        $constraint = new Present(['holder'], null, ['payment']);

        self::assertSame([], $this->violations(new Payment(null, null, null), $constraint));
        self::assertCount(1, $this->validator()->validate(new Payment(null, null, null), $constraint, ['payment']));
    }

    public function testSkipsNull(): void
    {
        self::assertSame([], $this->violations(null, new Present(['holder'])));
    }

    public function testReportsAValueThatIsNotAnObject(): void
    {
        self::assertSame(['This value should be of type object.'], $this->violations([], new Present(['holder'])));
    }

    public function testRejectsAPropertyTheClassLacks(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The class "stdClass" has no property "holder".');

        $this->violations(new stdClass(), new Present(['holder']));
    }

    public function testRejectsANameThatIsNotAString(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('Present takes a list of PHP property names.');

        new Present(['holder', 1]);
    }

    public function testRejectsAMapOfNames(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('Present takes a list of PHP property names.');

        new Present(['a' => 'holder']);
    }

    public function testRejectsAnotherConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new PresentValidator())->validate(new Payment(null, null, null), new NotNull());
    }

    public function testIsAClassConstraint(): void
    {
        self::assertSame(Constraint::CLASS_CONSTRAINT, (new Present([]))->getTargets());
    }

    public function testNamesItsError(): void
    {
        $this->requireErrorNames();

        self::assertSame('MISSING_ERROR', Present::getErrorName(Present::MISSING_ERROR));
    }
}
