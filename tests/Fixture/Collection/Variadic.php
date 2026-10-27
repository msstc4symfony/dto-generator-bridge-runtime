<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * Takes the items one by one, not as a list.
 */
final class Variadic
{
    /** @var list<Tag> */
    public array $tags;

    public function __construct(Tag ...$tags)
    {
        $this->tags = array_values($tags);
    }
}
