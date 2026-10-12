<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * JSON Schema's contains, minContains and maxContains: between $min and $max elements of a list pass all nested
 * constraints. Without constraints every element matches.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Contains extends Composite
{
    public const TOO_FEW_ERROR = '566516a6-b578-44e8-9500-49a022bf56bd';

    public const TOO_MANY_ERROR = '6952b19d-eab7-4c16-bf15-99c193665b32';

    protected const ERROR_NAMES = [self::TOO_FEW_ERROR => 'TOO_FEW_ERROR', self::TOO_MANY_ERROR => 'TOO_MANY_ERROR'];

    /**
     * @var list<Constraint>
     */
    public array $constraints;

    public int $min;

    public ?int $max;

    public string $minMessage = 'This collection should contain {{ limit }} matching element or more.|This collection should contain {{ limit }} matching elements or more.';

    public string $maxMessage = 'This collection should contain {{ limit }} matching element or less.|This collection should contain {{ limit }} matching elements or less.';

    /**
     * @param list<Constraint> $constraints
     * @param list<string>|null $groups
     */
    public function __construct(
        array $constraints = [],
        int $min = 1,
        ?int $max = null,
        ?string $minMessage = null,
        ?string $maxMessage = null,
        ?array $groups = null
    ) {
        if ($min < 0 || ($max !== null && $max < $min)) {
            throw new ConstraintDefinitionException(sprintf('The minimum of Contains must be 0 or more, and its maximum not below it; got %d and %s.', $min, $max ?? 'none'));
        }

        // Composite reads the nested constraints in its constructor.
        $this->constraints = $constraints;
        $this->min = $min;
        $this->max = $max;
        $this->minMessage = $minMessage ?? $this->minMessage;
        $this->maxMessage = $maxMessage ?? $this->maxMessage;
        parent::__construct(null, $groups);
    }

    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
