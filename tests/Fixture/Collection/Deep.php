<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use ArrayIterator;
use IteratorAggregate;

/**
 * Iterable, with a toArray() that wants an argument.
 *
 * @implements IteratorAggregate<int, int>
 */
final class Deep implements IteratorAggregate
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
     * @return list<int>
     */
    public function toArray(bool $deep): array
    {
        return $deep ? [] : [0];
    }

    /**
     * @return ArrayIterator<int, int>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->numbers);
    }
}
