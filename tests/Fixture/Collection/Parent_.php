<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * Read through a toArray() its subclass overrides.
 */
class Parent_
{
    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return ['parent'];
    }
}
