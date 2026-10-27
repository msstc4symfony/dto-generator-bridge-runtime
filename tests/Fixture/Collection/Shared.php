<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use ArrayIterator;
use IteratorAggregate;

/**
 * Iterable, with a static toArray() that reads no object.
 *
 * @implements IteratorAggregate<int, int>
 */
final class Shared implements IteratorAggregate
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
    public static function toArray(): array
    {
        return [];
    }

    /**
     * @return ArrayIterator<int, int>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->numbers);
    }
}
