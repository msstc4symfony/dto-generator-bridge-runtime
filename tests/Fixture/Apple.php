<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\AdditionalProperties;

final class Apple implements Fruit
{
    public int $size = 0;

    /**
     * @var array<string, string>
     */
    #[AdditionalProperties]
    public array $additionalProperties = [];
}
