<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use ArrayIterator;
use IteratorAggregate;

/**
 * Iterable, with a private toArray() that __call makes callable from outside.
 *
 * @implements IteratorAggregate<int, int>
 */
final class Called implements IteratorAggregate
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
     * @param list<mixed> $arguments
     *
     * @return list<string>
     */
    public function __call(string $name, array $arguments): array
    {
        return [$name, $this->toArray()[0] ?? ''];
    }

    /**
     * @return ArrayIterator<int, int>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->numbers);
    }

    /**
     * @return list<string>
     */
    private function toArray(): array
    {
        return ['hidden'];
    }
}
