<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A real validator, so the constraints meet the context and the nested validation of every supported Symfony.
 */
trait Validates
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidator();
    }

    /**
     * Reads attributes with AttributeLoader from 6.4, or with AnnotationLoader before 7.0, which reads them when it has
     * no Doctrine reader. Class names are strings because no version has both.
     */
    private function mappedValidator(): ValidatorInterface
    {
        $class = $this->loader('AttributeLoader');
        $loader = new $class();
        self::assertInstanceOf(LoaderInterface::class, $loader);

        return Validation::createValidatorBuilder()->addLoader($loader)->getValidator();
    }

    /**
     * The FQCN of a metadata loader, of AnnotationLoader when the version lacks it.
     */
    private function loader(string $name): string
    {
        $class = 'Symfony\\Component\\Validator\\Mapping\\Loader\\' . $name;

        return class_exists($class) ? $class : 'Symfony\\Component\\Validator\\Mapping\\Loader\\AnnotationLoader';
    }

    /**
     * Symfony before 6.1 reads the names of error codes from a static property, which the constraints leave out.
     */
    private function requireErrorNames(): void
    {
        if (!defined(Constraint::class . '::ERROR_NAMES')) {
            self::markTestSkipped('Symfony before 6.1 does not read Constraint::ERROR_NAMES.');
        }
    }

    /**
     * @param scalar|array<array-key, mixed>|object|resource|null $value
     *
     * @return list<string>
     */
    private function violations($value, Constraint $constraint): array
    {
        return $this->messages($this->validator()->validate($value, $constraint));
    }

    /**
     * @return list<string> "path: message", or the message alone at the root
     */
    private function messages(ConstraintViolationListInterface $violations): array
    {
        $messages = [];
        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath();
            $messages[] = ($path === '' ? '' : $path . ': ') . $violation->getMessage();
        }

        return $messages;
    }
}
