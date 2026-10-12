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

        $values = (array) $value;
        $missing = [];
        foreach ($constraint->requirements as $property => $dependents) {
            if (!PropertyKey::isSet($values, $value, $property)) {
                continue;
            }

            foreach ($dependents as $dependent) {
                if (!in_array($dependent, $missing, true) && !PropertyKey::isSet($values, $value, $dependent)) {
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
}
