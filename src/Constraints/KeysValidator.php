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
final class KeysValidator extends ConstraintValidator
{
    /**
     * @phpstan-param mixed $value any value: the parent declares a native mixed, which PHP 7.4 lacks
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Keys) {
            throw new UnexpectedTypeException($constraint, Keys::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $validator = $this->context->getValidator()->inContext($this->context);
        foreach (array_keys($value) as $key) {
            // PHP turns numeric string keys into ints; JSON names are strings.
            $validator->atPath('[' . $key . ']')->validate((string) $key, $constraint->constraints);
        }
    }
}
