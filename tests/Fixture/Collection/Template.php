<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * Abstract, with a factory that cannot build it.
 */
abstract class Template
{
    final public function __construct()
    {
    }

    /**
     * @param list<mixed> $items
     */
    public static function fromArray(array $items): self
    {
        return new static();
    }
}
