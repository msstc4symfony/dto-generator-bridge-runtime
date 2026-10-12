<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\DependentRequired;

/**
 * The properties of every visibility, as the generator writes them for PHP 7.4 and 8.
 */
#[DependentRequired(['card' => ['holder', 'expiry']])]
final class Payment extends Charge
{
    public ?string $card;

    private ?string $holder;

    private ?string $expiry;

    public function __construct(?string $card, ?string $holder, ?string $expiry, ?int $amount = null, ?string $currency = null)
    {
        parent::__construct($amount, $currency);
        $this->card = $card;
        $this->holder = $holder;
        $this->expiry = $expiry;
    }

    public function getHolder(): ?string
    {
        return $this->holder;
    }

    public function getExpiry(): ?string
    {
        return $this->expiry;
    }
}
