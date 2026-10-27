<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

final class Child extends Parent_
{
    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return ['child'];
    }
}
