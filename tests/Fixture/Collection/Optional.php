<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * A constructor of a nullable list.
 */
final class Optional
{
    /** @var list<mixed>|null */
    public ?array $items;

    /**
     * @param list<mixed>|null $items
     */
    public function __construct(?array $items = null)
    {
        $this->items = $items;
    }
}
