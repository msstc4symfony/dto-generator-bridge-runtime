<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * A factory with a bug of its own: one item divides by zero.
 */
final class Failing
{
    public int $share;

    private function __construct(int $share)
    {
        $this->share = $share;
    }

    /**
     * @param list<mixed> $items
     */
    public static function fromArray(array $items): self
    {
        return new self(intdiv(100, count($items) - 1));
    }
}
