<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * A constructor whose one parameter is no list.
 */
final class Named
{
    public string $name;

    public function __construct(string $name = 'x')
    {
        $this->name = $name;
    }
}
