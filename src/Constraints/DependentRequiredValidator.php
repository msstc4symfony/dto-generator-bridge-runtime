<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @api
 */
final class DependentRequiredValidator extends ConstraintValidator
{
    /**
     * @phpstan-param mixed $value any value: the parent declares a native mixed, which PHP 7.4 lacks
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof DependentRequired) {
            throw new UnexpectedTypeException($constraint, DependentRequired::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_object($value)) {
            throw new UnexpectedValueException($value, 'object');
        }

        // The array cast reaches protected and private properties, also of parents, without
        // ReflectionProperty::setAccessible(), which PHP 7.4 needs and PHP 8.5 deprecates.
        $values = (array) $value;
        foreach ($constraint->requirements as $property => $dependents) {
            if (!$this->isSet($values, $property, $value)) {
                continue;
            }

            foreach ($dependents as $dependent) {
                if (!$this->isSet($values, $dependent, $value)) {
                    $this->context->buildViolation($constraint->message)
                        ->atPath($dependent)
                        ->setParameter('{{ property }}', $this->formatValue($property))
                        ->setInvalidValue(null)
                        ->setCode(DependentRequired::MISSING_ERROR)
                        ->addViolation()
                    ;
                }
            }
        }
    }

    /**
     * @param array<array-key, mixed> $values the object as an array: private and protected keys are "\0Class\0name"
     *                                        and "\0*\0name"
     */
    private function isSet(array $values, string $property, object $object): bool
    {
        $suffix = "\0" . $property;
        foreach ($values as $key => $value) {
            // A public key gets the separator here, so all three forms end alike.
            if (substr("\0" . $key, -strlen($suffix)) === $suffix) {
                return $value !== null;
            }
        }

        throw new ConstraintDefinitionException(sprintf('The class "%s" has no property "%s".', get_class($object), $property));
    }
}
