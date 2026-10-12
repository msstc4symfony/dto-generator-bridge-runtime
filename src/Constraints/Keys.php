<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;

/**
 * JSON Schema's propertyNames on a map: each key, as a string, must pass the nested constraints.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Keys extends Composite
{
    /**
     * @var list<Constraint>
     */
    public array $constraints;

    /**
     * @param list<Constraint> $constraints
     * @param list<string>|null $groups
     */
    public function __construct(array $constraints, ?array $groups = null)
    {
        // Composite reads the nested constraints in its constructor.
        $this->constraints = $constraints;
        parent::__construct(null, $groups);
    }

    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
