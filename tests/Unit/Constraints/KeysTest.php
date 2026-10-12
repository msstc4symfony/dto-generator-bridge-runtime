<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use ArrayIterator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Keys;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\KeysValidator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Catalogue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class KeysTest extends TestCase
{
    use Validates;

    public function testChecksEachKeyAtItsEntry(): void
    {
        $violations = $this->validator()->validate(['x-a' => 1, 'b' => 2, 'c' => 3], new Keys([new Regex('/^x-/')]));

        self::assertSame(['[b]: This value is not valid.', '[c]: This value is not valid.'], $this->messages($violations));
        self::assertSame('b', $violations->get(0)->getInvalidValue());
    }

    public function testChecksEveryNestedConstraint(): void
    {
        self::assertSame(
            ['[b]: This value is not valid.', '[b]: This value is too short. It should have 2 characters or more.'],
            $this->violations(['b' => 1], new Keys([new Regex('/^x-/'), new Length(null, 2)])),
        );
    }

    public function testGivesNumericKeysAsStrings(): void
    {
        self::assertSame([], $this->violations([200 => 'ok'], new Keys([new Type('string')])));
    }

    public function testReportsATraversable(): void
    {
        self::assertSame(['This value should be of type array.'], $this->violations(new ArrayIterator(['b' => 1]), new Keys([new Regex('/^x-/')])));
    }

    public function testChecksOnlyInItsGroups(): void
    {
        $constraint = new Keys([new Regex('/^x-/')], ['names']);

        self::assertSame([], $this->violations(['b' => 1], $constraint));
        self::assertCount(1, $this->validator()->validate(['b' => 1], $constraint, ['names']));
    }

    public function testSkipsNull(): void
    {
        self::assertSame([], $this->violations(null, new Keys([new Regex('/^x-/')])));
    }

    public function testReportsAValueThatIsNotAnArray(): void
    {
        self::assertSame(['This value should be of type array.'], $this->violations('b', new Keys([new Regex('/^x-/')])));
    }

    public function testRejectsAnotherConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new KeysValidator())->validate([], new NotNull());
    }

    /**
     * @requires PHP 8.1
     */
    public function testIsReadFromThePropertyAttribute(): void
    {
        self::assertSame(['extra[b]: This value is not valid.'], $this->messages($this->validateMapped($this->mappedValidator(), new Catalogue(['x-a' => 1, 'b' => 2]))));
    }
}
