<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

/**
 * Declares a private $amount of its own beside the one of Charge.
 */
final class Refund extends Charge
{
    private ?int $amount;

    private ?string $reason;

    public function __construct(?int $amount, ?int $charged, ?string $reason)
    {
        parent::__construct($charged);
        $this->amount = $amount;
        $this->reason = $reason;
    }

    public function getRefunded(): ?int
    {
        return $this->amount;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
