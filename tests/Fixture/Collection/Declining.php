<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * A factory that gives no collection, beside a constructor that would.
 */
final class Declining
{
    /** @var list<mixed> */
    public array $items;

    /**
     * @param list<mixed> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @param list<mixed> $items
     */
    public static function fromArray(array $items): ?self
    {
        return $items === [] ? new self([]) : null;
    }
}
