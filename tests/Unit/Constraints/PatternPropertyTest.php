<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use ArrayIterator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\PatternProperty;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\PatternPropertyValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Shipment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class PatternPropertyTest extends TestCase
{
    use Validates;

    public function testChecksTheValuesOfMatchingKeysAtTheirEntries(): void
    {
        $violations = $this->validator()->validate(['x-a' => 'long', 'b' => 'long', 'x-c' => 'ok'], new PatternProperty('/^x-/', [new Length(null, null, 3)]));

        self::assertSame(['[x-a]: This value is too long. It should have 3 characters or less.'], $this->messages($violations));
        self::assertSame('long', $violations->get(0)->getInvalidValue());
    }

    public function testMatchesNumericKeysAsStrings(): void
    {
        self::assertSame(['[20]: This value should be of type string.'], $this->violations([20 => 1, 3 => 1], new PatternProperty('/^2/', [new Type('string')])));
    }

    public function testChecksEveryNestedConstraint(): void
    {
        self::assertSame(
            ['[a]: This value should not be null.', '[b]: This value should be of type string.'],
            $this->violations(['a' => null, 'b' => 1], new PatternProperty('/./', [new NotNull(), new Type('string')])),
        );
    }

    public function testChecksOnlyInItsGroups(): void
    {
        $constraint = new PatternProperty('/./', [new NotNull()], ['labels']);

        self::assertSame([], $this->violations(['a' => null], $constraint));
        self::assertCount(1, $this->validator()->validate(['a' => null], $constraint, ['labels']));
    }

    public function testSkipsNull(): void
    {
        self::assertSame([], $this->violations(null, new PatternProperty('/./', [new NotNull()])));
    }

    public function testReportsAValueThatIsNotAnArray(): void
    {
        self::assertSame(['This value should be of type array.'], $this->violations(new ArrayIterator(['a' => null]), new PatternProperty('/./', [new NotNull()])));
    }

    public function testRejectsAnInvalidPattern(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The pattern "/(/" of PatternProperty is no PCRE regular expression.');

        new PatternProperty('/(/');
    }

    public function testRejectsAnotherConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new PatternPropertyValidator())->validate([], new NotNull());
    }

    /**
     * @requires PHP 8.1
     */
    public function testIsReadFromThePropertyAttribute(): void
    {
        self::assertSame(
            ['labels[x-a]: This value is too long. It should have 3 characters or less.'],
            $this->messages($this->validateMapped($this->mappedValidator(), new Shipment('DE', null, null, ['x-a' => 'long', 'b' => 'long']))),
        );
    }
}
