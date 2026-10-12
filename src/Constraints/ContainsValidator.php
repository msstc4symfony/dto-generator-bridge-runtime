<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @api
 */
final class ContainsValidator extends ConstraintValidator
{
    /**
     * @phpstan-param mixed $value any value: the parent declares a native mixed, which PHP 7.4 lacks
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Contains) {
            throw new UnexpectedTypeException($constraint, Contains::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        // The elements' validation re-initializes this validator when they hold Contains too (Symfony before 8.1 shares
        // one instance), so the violations go to the context taken here.
        $context = $this->context;
        $count = $this->matching($value, $constraint, $context);
        if ($count < $constraint->min) {
            $this->report($context, $value, $constraint->minMessage, $count, $constraint->min, Contains::TOO_FEW_ERROR);
        } elseif ($constraint->max !== null && $count > $constraint->max) {
            $this->report($context, $value, $constraint->maxMessage, $count, $constraint->max, Contains::TOO_MANY_ERROR);
        }
    }

    /**
     * An element matches when a validation of its own, apart from the context, finds nothing: its violations are no
     * violations of the list.
     *
     * @param array<array-key, mixed> $value
     */
    private function matching(array $value, Contains $constraint, ExecutionContextInterface $context): int
    {
        $validator = $context->getValidator();
        $group = $context->getGroup();
        $count = 0;
        foreach ($value as $element) {
            if (count($validator->validate($element, $constraint->constraints, $group)) === 0) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function report(ExecutionContextInterface $context, array $value, string $message, int $count, int $limit, string $code): void
    {
        $context->buildViolation($message)
            ->setParameter('{{ count }}', (string) $count)
            ->setParameter('{{ limit }}', (string) $limit)
            ->setInvalidValue($value)
            ->setPlural($limit)
            ->setCode($code)
            ->addViolation()
        ;
    }
}
