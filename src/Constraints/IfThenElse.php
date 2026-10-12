<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * JSON Schema's if, then and else: a value that passes the constraints of $if passes those of $then, any other value
 * those of $else. On a class, Property and Present check its properties.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @api
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class IfThenElse extends Composite
{
    /**
     * @internal for IfThenElseValidator
     *
     * @var array{Conjunction, Conjunction, Conjunction} if, then and else
     */
    public array $branches;

    /**
     * @param list<Constraint> $if
     * @param list<Constraint> $then
     * @param list<Constraint> $else
     * @param list<string>|null $groups those of the nested constraints when null
     */
    public function __construct(array $if = [], array $then = [], array $else = [], ?array $groups = null)
    {
        $nested = array_merge($if, $then, $else);
        $groups ??= $this->groupsOf($nested);
        // Composite reads the nested constraints in its constructor.
        $this->branches = [new Conjunction($if, $groups), new Conjunction($then, $groups), new Conjunction($else, $groups)];
        parent::__construct(null, $groups);
        foreach ($nested as $constraint) {
            if (array_diff($groups, $constraint->groups ?? []) !== []) {
                throw new ConstraintDefinitionException('The constraints inside IfThenElse must be in all of its groups, or "if" would match on part of them.');
            }
        }
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
        return 'branches';
    }

    /**
     * @param list<Constraint> $constraints
     *
     * @return list<string>
     */
    private function groupsOf(array $constraints): array
    {
        $groups = [];
        foreach ($constraints as $constraint) {
            foreach ($constraint->groups ?? [] as $group) {
                $groups[$group] = $group;
            }
        }

        return $groups === [] ? [self::DEFAULT_GROUP] : array_values($groups);
    }
}
