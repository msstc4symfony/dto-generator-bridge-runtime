<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * Takes the list by reference, which a call through reflection cannot pass.
 */
final class ByReference
{
    /** @var list<mixed> */
    public array $items;

    /**
     * @param list<mixed> $items
     */
    public function __construct(array &$items)
    {
        $this->items = $items;
    }
}
