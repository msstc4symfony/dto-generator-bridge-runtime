<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Not;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Present;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The attributes stay on one line each: PHP 7.4 reads such a line as a comment.
 */
#[Not([new Present(['code', 'card'])])]
final class Voucher
{
    #[Not([new Assert\IdenticalTo('TEST')])]
    public ?string $code;

    public ?string $card;

    public function __construct(?string $code, ?string $card)
    {
        $this->code = $code;
        $this->card = $card;
    }
}
