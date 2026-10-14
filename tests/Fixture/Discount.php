<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\ExactlyOneOf;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The attributes stay on one line each: PHP 7.4 reads such a line as a comment.
 */
final class Discount
{
    #[ExactlyOneOf([new Assert\Regex('/^[0-9]+%$/'), new Assert\Length(null, null, 3)])]
    public ?string $amount;

    public function __construct(?string $amount)
    {
        $this->amount = $amount;
    }
}
