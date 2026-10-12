<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

class Charge
{
    private ?int $amount;

    public function __construct(?int $amount)
    {
        $this->amount = $amount;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }
}
