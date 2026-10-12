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
final class PropertyValidator extends ConstraintValidator
{
    /**
     * @phpstan-param mixed $value any value: the parent declares a native mixed, which PHP 7.4 lacks
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Property) {
            throw new UnexpectedTypeException($constraint, Property::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_object($value)) {
            throw new UnexpectedValueException($value, 'object');
        }

        $values = (array) $value;
        $key = PropertyKey::find($values, $value, $constraint->name);
        if ($key === null || $values[$key] === null) {
            return;
        }

        $this->context->getValidator()->inContext($this->context)->atPath($constraint->name)->validate($values[$key], $constraint->constraints);
    }
}
