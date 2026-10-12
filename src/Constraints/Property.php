<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;

/**
 * JSON Schema's properties on a generated class, for the branches of IfThenElse: the value of a property passes the
 * nested constraints. A missing property (null or uninitialized) passes, as a missing key does.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "ANNOTATION"})
 *
 * @api
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Property extends Composite
{
    public string $name;

    /**
     * @var list<Constraint>
     */
    public array $constraints;

    /**
     * @param string $name the PHP name of the property
     * @param list<Constraint> $constraints
     * @param list<string>|null $groups
     */
    public function __construct(string $name, array $constraints = [], ?array $groups = null)
    {
        $this->name = $name;
        // Composite reads the nested constraints in its constructor.
        $this->constraints = $constraints;
        parent::__construct(null, $groups);
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * The nested constraints check the value of the property, not the object, so the metadata of the class must not
     * require them to be class constraints, as newer Symfony versions do with what this returns.
     *
     * @return list<Constraint>
     */
    public function getNestedConstraints(): array
    {
        return [];
    }

    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
