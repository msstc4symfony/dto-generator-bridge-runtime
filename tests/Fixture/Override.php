<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

/**
 * Declares a public $amount beside the private one of Charge, which it cannot see.
 */
final class Override extends Charge
{
    public ?int $amount;

    public function __construct(?int $amount, ?int $charged)
    {
        parent::__construct($charged);
        $this->amount = $amount;
    }
}
