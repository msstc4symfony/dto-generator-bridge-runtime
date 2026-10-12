<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

class Charge
{
    protected ?string $currency;

    private ?int $amount;

    public function __construct(?int $amount, ?string $currency = null)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }
}
