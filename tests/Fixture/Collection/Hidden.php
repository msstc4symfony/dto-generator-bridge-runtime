<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * Built only from inside: neither its factory nor its constructor is public.
 */
final class Hidden
{
    private function __construct()
    {
    }

    public static function empty(): self
    {
        return self::fromArray([]);
    }

    /**
     * @param list<mixed> $items
     */
    private static function fromArray(array $items): self
    {
        return $items === [] ? new self() : new self();
    }
}
