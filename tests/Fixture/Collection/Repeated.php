<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use Generator;
use IteratorAggregate;

/**
 * Iterated with the same key for every item, as a generator may be.
 *
 * @implements IteratorAggregate<int, int>
 */
final class Repeated implements IteratorAggregate
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
     * @return Generator<int, int, mixed, void>
     */
    public function getIterator(): Generator
    {
        foreach ($this->numbers as $number) {
            yield 0 => $number;
        }
    }
}
