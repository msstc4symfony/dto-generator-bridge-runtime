<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @api
 */
final class PresentValidator extends ConstraintValidator
{
    /**
     * @phpstan-param mixed $value any value: the parent declares a native mixed, which PHP 7.4 lacks
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Present) {
            throw new UnexpectedTypeException($constraint, Present::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_object($value)) {
            throw new UnexpectedValueException($value, 'object');
        }

        $values = (array) $value;
        foreach ($constraint->properties as $property) {
            if (!PropertyKey::isSet($values, $value, $property)) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($property)
                    ->setParameter('{{ field }}', $this->formatValue($property))
                    ->setInvalidValue(null)
                    ->setCode(Present::MISSING_ERROR)
                    ->addViolation()
                ;
            }
        }
    }
}
