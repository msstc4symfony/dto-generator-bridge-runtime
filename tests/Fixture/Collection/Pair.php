<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * A constructor that wants more than the list.
 */
final class Pair
{
    /** @var list<mixed> */
    public array $items;

    public int $size;

    /**
     * @param list<mixed> $items
     */
    public function __construct(array $items, int $size)
    {
        $this->items = $items;
        $this->size = $size;
    }
}
