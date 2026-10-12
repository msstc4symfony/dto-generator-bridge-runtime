<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * JSON Schema's patternProperties, one pattern each: the value of every key of an array that matches the PCRE pattern
 * passes the nested constraints. Keys are matched as strings.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class PatternProperty extends Composite
{
    public string $pattern;

    /**
     * @var list<Constraint>
     */
    public array $constraints;

    /**
     * @param string $pattern a delimited PCRE pattern
     * @param list<Constraint> $constraints
     * @param list<string>|null $groups
     */
    public function __construct(string $pattern, array $constraints = [], ?array $groups = null)
    {
        if (@preg_match($pattern, '') === false) {
            throw new ConstraintDefinitionException(sprintf('The pattern "%s" of PatternProperty is no PCRE regular expression.', $pattern));
        }

        $this->pattern = $pattern;
        // Composite reads the nested constraints in its constructor.
        $this->constraints = $constraints;
        parent::__construct(null, $groups);
    }

    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
