<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @api
 */
final class ExactlyOneOfValidator extends ConstraintValidator
{
    /**
     * @phpstan-param mixed $value any value: the parent declares a native mixed, which PHP 7.4 lacks
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExactlyOneOf) {
            throw new UnexpectedTypeException($constraint, ExactlyOneOf::class);
        }

        if ($value === null) {
            return;
        }

        // Checking the constraints re-initializes this validator when they hold a composite too (Symfony before 8.1
        // shares one instance), so the violation goes to the context taken here.
        $context = $this->context;
        $passed = 0;
        foreach ($constraint->constraints as $member) {
            // A validation of its own, apart from the context: the violations of a member are no violations of the value.
            if (count($context->getValidator()->validate($value, $member, $context->getGroup())) === 0) {
                $passed++;
            }
        }

        if ($passed === 1) {
            return;
        }

        $context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->setParameter('{{ count }}', (string) $passed)
            ->setCode(ExactlyOneOf::NOT_EXACTLY_ONE_ERROR)
            ->addViolation()
        ;
    }
}
