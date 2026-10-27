<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * A fromArray() that is no factory, beside a constructor that takes the list.
 */
final class Instanced
{
    /** @var list<mixed> */
    private array $items;

    /**
     * @param list<mixed> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function fromArray(): self
    {
        return new self([]);
    }

    /**
     * @return list<mixed>
     */
    public function items(): array
    {
        return $this->items;
    }
}
