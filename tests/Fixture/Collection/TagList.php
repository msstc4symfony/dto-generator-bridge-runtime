<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * Built through a named constructor and read through toArray(), as dto-collection's lists are.
 */
final class TagList
{
    /** @var list<Tag> */
    private array $tags;

    /**
     * @param list<Tag> $tags
     */
    private function __construct(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * @param list<Tag> $tags
     */
    public static function fromArray(array $tags): self
    {
        return new self($tags);
    }

    /**
     * @return list<Tag>
     */
    public function toArray(): array
    {
        return $this->tags;
    }
}
