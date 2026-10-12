<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * JSON Schema's dependentRequired on a generated class: each property listed in $requirements, once set, needs the
 * properties listed with it. A property counts as set when it is not null; a DTO cannot tell a missing key from null.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "ANNOTATION"})
 *
 * @api
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class DependentRequired extends Constraint
{
    public const MISSING_ERROR = '623acbf9-cc87-4af2-99a2-e04f055e0c0a';

    protected const ERROR_NAMES = [self::MISSING_ERROR => 'MISSING_ERROR'];

    /**
     * @var array<string, list<string>> PHP property name => the PHP names of the properties it needs
     */
    public array $requirements;

    public string $message = 'This value should not be null when {{ property }} is set.';

    /**
     * @param array<string, list<string>> $requirements PHP property name => the PHP names of the properties it needs
     * @param list<string>|null $groups
     */
    public function __construct(array $requirements, ?string $message = null, ?array $groups = null)
    {
        parent::__construct(null, $groups);
        $this->requirements = $requirements;
        $this->message = $message ?? $this->message;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
