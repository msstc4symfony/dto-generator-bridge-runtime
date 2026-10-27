<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * A factory that wants more than the list.
 */
final class Sized
{
    private function __construct()
    {
    }

    /**
     * @param list<mixed> $items
     */
    public static function fromArray(array $items, int $size): self
    {
        return new self();
    }
}
