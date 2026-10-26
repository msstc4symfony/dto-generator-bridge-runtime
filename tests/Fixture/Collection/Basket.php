<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

use Symfony\Component\Serializer\Attribute\Context;

/**
 * As the bridge generates it: the context names the collection class and how the Serializer types its items.
 */
final class Basket
{
    #[Context(normalizationContext: ['dto_generator_collection' => ['class' => TagList::class, 'item' => Tag::class]], denormalizationContext: ['dto_generator_collection' => ['class' => TagList::class, 'item' => Tag::class]])]
    private TagList $tags;

    #[Context(normalizationContext: ['dto_generator_collection' => ['class' => Numbers::class, 'item' => 'float']], denormalizationContext: ['dto_generator_collection' => ['class' => Numbers::class, 'item' => 'float']])]
    private ?Numbers $prices;

    public function __construct(TagList $tags, ?Numbers $prices = null)
    {
        $this->tags = $tags;
        $this->prices = $prices;
    }

    public function getTags(): TagList
    {
        return $this->tags;
    }

    public function getPrices(): ?Numbers
    {
        return $this->prices;
    }
}
