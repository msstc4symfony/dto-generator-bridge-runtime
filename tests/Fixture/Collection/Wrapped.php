<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use ArrayObject;

/**
 * A toArray() that gives an iterable, not an array.
 */
final class Wrapped
{
    /** @var list<int> */
    private array $numbers;

    /**
     * @param list<int> $numbers
     */
    public function __construct(array $numbers)
    {
        $this->numbers = $numbers;
    }

    /**
     * @return ArrayObject<int, int>
     */
    public function toArray(): ArrayObject
    {
        return new ArrayObject($this->numbers);
    }
}
