<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use ArrayIterator;
use IteratorAggregate;

/**
 * Iterable, with a toArray() of its own that is not public.
 *
 * @implements IteratorAggregate<int, int>
 */
class Guarded implements IteratorAggregate
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
     * @return ArrayIterator<int, int>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->numbers);
    }
}
