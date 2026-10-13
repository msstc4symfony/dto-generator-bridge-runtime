<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * JSON Schema's not: a value that passes all of the constraints is a violation. On a class, Property and Present check
 * its properties.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @api
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Not extends Composite
{
    public const NOT_ALLOWED_ERROR = '6f4c2b1e-8d3a-4e7f-9b2c-5a1d0e3f7c84';

    protected const ERROR_NAMES = [self::NOT_ALLOWED_ERROR => 'NOT_ALLOWED_ERROR'];

    /**
     * @var list<Constraint>
     */
    public array $constraints;

    public string $message = 'This value is not allowed.';

    /**
     * @param list<Constraint> $constraints
     * @param list<string>|null $groups those of the nested constraints when null
     */
    public function __construct(array $constraints = [], ?string $message = null, ?array $groups = null)
    {
        $this->constraints = $constraints;
        $this->message = $message ?? $this->message;
        $groups ??= $this->groupsOf($constraints);
        parent::__construct(null, $groups);
        foreach ($constraints as $constraint) {
            if (array_diff($groups, $constraint->groups ?? []) !== []) {
                throw new ConstraintDefinitionException('The constraints inside Not must be in all of its groups, or it would match on part of them.');
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
        return 'constraints';
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
