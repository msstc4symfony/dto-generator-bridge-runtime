<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use ArrayIterator;
use BadMethodCallException;
use IteratorAggregate;

/**
 * Iterable, with __call for every method it lacks.
 *
 * @implements IteratorAggregate<int, int>
 */
final class Magic implements IteratorAggregate
{
    /** @var list<int> */
    private array $numbers;

    /**
     * @param iterable<int> $numbers
     */
    public function __construct(iterable $numbers)
    {
        $this->numbers = array_values(is_array($numbers) ? $numbers : iterator_to_array($numbers, false));
    }

    /**
     * @param list<mixed> $arguments
     *
     * @return never
     */
    public function __call(string $name, array $arguments)
    {
        throw new BadMethodCallException($name);
    }

    /**
     * @return ArrayIterator<int, int>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->numbers);
    }
}
