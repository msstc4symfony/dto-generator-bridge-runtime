<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;

/**
 * The constraints of one branch of IfThenElse, which holds three lists: Composite before Symfony 7.3 holds one.
 *
 * @internal
 */
final class Conjunction extends Composite
{
    /**
     * @var list<Constraint>
     */
    public array $constraints;

    /**
     * @param list<Constraint> $constraints
     * @param list<string> $groups
     */
    public function __construct(array $constraints, array $groups)
    {
        $this->constraints = $constraints;
        parent::__construct(null, $groups);
    }

    /**
     * @return list<self::PROPERTY_CONSTRAINT|self::CLASS_CONSTRAINT>
     */
    public function getTargets(): array
    {
        return [self::PROPERTY_CONSTRAINT, self::CLASS_CONSTRAINT];
    }

    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
