<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * A public constructor without parameters, which PHP would call with the list and drop it.
 */
final class Bare
{
    public int $arguments;

    public function __construct()
    {
        $this->arguments = \func_num_args();
    }
}
