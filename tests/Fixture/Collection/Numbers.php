<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use ArrayIterator;
use IteratorAggregate;

/**
 * Built through its constructor and read by iteration, as Doctrine's ArrayCollection is.
 *
 * @implements IteratorAggregate<int, float>
 */
final class Numbers implements IteratorAggregate
{
    /** @var list<float> */
    private array $numbers;

    /**
     * @param list<float> $numbers
     */
    public function __construct(array $numbers)
    {
        $this->numbers = $numbers;
    }

    /**
     * @return ArrayIterator<int, float>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->numbers);
    }
}
