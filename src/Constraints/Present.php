<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * JSON Schema's required on a generated class, for the branches of IfThenElse: each listed property is set. A property
 * counts as set when it is not null; a DTO cannot tell a missing key from null.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "ANNOTATION"})
 *
 * @api
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Present extends Constraint
{
    public const MISSING_ERROR = 'b3b7d5b8-5e4f-4d0c-9a0e-2f6b8f2c7d41';

    protected const ERROR_NAMES = [self::MISSING_ERROR => 'MISSING_ERROR'];

    /**
     * @var list<string> PHP property names
     */
    public array $properties;

    public string $message = 'This field is missing.';

    /**
     * @param array<array-key, mixed> $properties PHP property names; mixed and checked here, as annotations and
     *                                            attributes are not type-checked
     * @param list<string>|null $groups
     */
    public function __construct(array $properties, ?string $message = null, ?array $groups = null)
    {
        parent::__construct(null, $groups);
        if (array_values($properties) !== $properties || array_filter($properties, 'is_string') !== $properties) {
            throw new ConstraintDefinitionException('Present takes a list of PHP property names.');
        }

        $this->properties = $properties;
        $this->message = $message ?? $this->message;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
