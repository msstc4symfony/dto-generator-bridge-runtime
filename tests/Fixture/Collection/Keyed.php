<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection;

/**
 * Read through a toArray() that keeps the keys it was given.
 */
final class Keyed
{
    /** @var array<string, int> */
    private array $numbers;

    /**
     * @param array<string, int> $numbers
     */
    public function __construct(array $numbers)
    {
        $this->numbers = $numbers;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return $this->numbers;
    }
}
