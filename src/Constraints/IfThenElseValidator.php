<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @api
 */
final class IfThenElseValidator extends ConstraintValidator
{
    /**
     * @phpstan-param mixed $value any value: the parent declares a native mixed, which PHP 7.4 lacks
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IfThenElse) {
            throw new UnexpectedTypeException($constraint, IfThenElse::class);
        }

        if ($value === null) {
            return;
        }

        // Checking "if" re-initializes this validator when it holds IfThenElse too (Symfony before 8.1 shares one
        // instance), so the branch is checked in the context taken here.
        $context = $this->context;
        [$if, $then, $else] = $constraint->branches;
        // A validation of its own, apart from the context: the violations of "if" are no violations of the value.
        $matches = count($context->getValidator()->validate($value, $if->constraints, $context->getGroup())) === 0;
        $context->getValidator()->inContext($context)->validate($value, ($matches ? $then : $else)->constraints);
    }
}
