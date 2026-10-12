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
        $missing = [];
        foreach ($constraint->requirements as $property => $dependents) {
            if (!$this->isSet($values, $property, $value)) {
                continue;
            }

            foreach ($dependents as $dependent) {
                if (!in_array($dependent, $missing, true) && !$this->isSet($values, $dependent, $value)) {
                    $missing[] = $dependent;
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
     * @param array<array-key, mixed> $values the object as an array: "\0Class\0name" for a private property of Class,
     *                                        "\0*\0name" for a protected one; an uninitialized property is missing
     */
    private function isSet(array $values, string $property, object $object): bool
    {
        $classes = [];
        for ($class = get_class($object); $class !== false; $class = get_parent_class($class)) {
            $classes[] = $class;
        }

        $keys = array_merge(array_map(static fn (string $class): string => "\0" . $class . "\0" . $property, $classes), ["\0*\0" . $property, $property]);
        foreach ($keys as $key) {
            if (array_key_exists($key, $values)) {
                return $values[$key] !== null;
            }
        }

        foreach ($classes as $class) {
            if (property_exists($class, $property)) {
                return false;
            }
        }

        throw new ConstraintDefinitionException(sprintf('The class "%s" has no property "%s".', get_class($object), $property));
    }
}
