<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * JSON Schema's oneOf: the value passes exactly one of the constraints. A member of several constraints is one
 * constraint that holds them, such as Sequentially. On a class, Property and Present check its properties.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @api
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ExactlyOneOf extends Composite
{
    public const NOT_EXACTLY_ONE_ERROR = '3b8e1f6a-0c4d-4a2e-9f7b-d5c1e8a2b603';

    protected const ERROR_NAMES = [self::NOT_EXACTLY_ONE_ERROR => 'NOT_EXACTLY_ONE_ERROR'];

    /**
     * @var list<Constraint>
     */
    public array $constraints;

    public string $message = 'This value should satisfy exactly one of the given constraints; it satisfies {{ count }}.';

    /** Whether the violation of a value that satisfies none lists what each constraint said, as AtLeastOneOf does. */
    public bool $includeInternalMessages = true;

    /**
     * @param list<Constraint> $constraints
     * @param list<string>|null $groups those of the nested constraints when null
     */
    public function __construct(array $constraints, ?string $message = null, ?array $groups = null, ?bool $includeInternalMessages = null)
    {
        if ($constraints === []) {
            throw new ConstraintDefinitionException('ExactlyOneOf needs at least one constraint, as oneOf needs a schema.');
        }

        $this->constraints = $constraints;
        $this->message = $message ?? $this->message;
        $this->includeInternalMessages = $includeInternalMessages ?? $this->includeInternalMessages;
        $groups ??= $this->groupsOf($constraints);
        parent::__construct(null, $groups);
        foreach ($constraints as $constraint) {
            $missing = array_diff($groups, $constraint->groups ?? []);
            if ($missing !== []) {
                throw new ConstraintDefinitionException(sprintf('The constraints inside ExactlyOneOf must be in all of its groups, or it would count part of them: %s is not in "%s".', get_class($constraint), implode('", "', $missing)));
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

        return array_values($groups);
    }
}
