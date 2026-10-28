<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * A constructor whose parameter has no native type.
 */
final class Loose
{
    /** @var list<mixed> */
    public $items;

    /**
     * @param list<mixed> $items
     */
    public function __construct($items)
    {
        $this->items = $items;
    }
}
