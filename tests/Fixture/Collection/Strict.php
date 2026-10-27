<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use InvalidArgumentException;

/**
 * Its factory refuses items, as dto-collection's lists do with ElementTypeMismatch.
 */
final class Strict
{
    private function __construct()
    {
    }

    /**
     * @param list<mixed> $items
     */
    public static function fromArray(array $items): self
    {
        if (in_array(true, $items, true)) {
            throw new InvalidArgumentException('No booleans.');
        }

        return new self();
    }
}
