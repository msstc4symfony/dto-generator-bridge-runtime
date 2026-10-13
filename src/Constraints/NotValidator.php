<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @api
 */
final class NotValidator extends ConstraintValidator
{
    /**
     * @phpstan-param mixed $value any value: the parent declares a native mixed, which PHP 7.4 lacks
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Not) {
            throw new UnexpectedTypeException($constraint, Not::class);
        }

        if ($value === null) {
            return;
        }

        // Checking the constraints re-initializes this validator when they hold Not too (Symfony before 8.1 shares one
        // instance), so the violation goes to the context taken here.
        $context = $this->context;
        // A validation of its own, apart from the context: the violations of the constraints are no violations of the value.
        if (count($context->getValidator()->validate($value, $constraint->constraints, $context->getGroup())) !== 0) {
            return;
        }

        $context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->setCode(Not::NOT_ALLOWED_ERROR)
            ->addViolation()
        ;
    }
}
