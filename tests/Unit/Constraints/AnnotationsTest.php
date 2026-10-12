<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use Doctrine\Common\Annotations\AnnotationReader;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Annotated;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Validation;

/**
 * The annotations the generator writes for PHP 7.4, read by Doctrine's reader as Symfony before 7.0 reads them.
 */
final class AnnotationsTest extends TestCase
{
    use Validates;

    public function testReadsTheConstraintsFromAnnotations(): void
    {
        $loader = $this->loader('AnnotationLoader');
        if (!class_exists($loader) || !class_exists(AnnotationReader::class)) {
            self::markTestSkipped('Symfony Validator before 7.0 and doctrine/annotations read annotations.');
        }

        $loader = new $loader(new AnnotationReader());
        self::assertInstanceOf(LoaderInterface::class, $loader);
        $validator = Validation::createValidatorBuilder()->addLoader($loader)->getValidator();

        self::assertSame([], $this->messages($this->validateMapped($validator, new Annotated('4242', 'Ann', ['admin'], ['abc' => 1]))));
        self::assertSame(
            [
                'holder: This value should not be null when "card" is set.',
                'roles: This collection should contain 1 matching element or less.',
                'extra[abcd]: This value is too long. It should have 3 characters or less.',
            ],
            $this->messages($this->validateMapped($validator, new Annotated('4242', null, ['admin', 'admin'], ['abcd' => 1]))),
        );
    }
}
