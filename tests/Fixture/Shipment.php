<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\IfThenElse;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\PatternProperty;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Present;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Property;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The attributes stay on one line each: PHP 7.4 reads such a line as a comment.
 */
#[IfThenElse(if: [new Property('country', [new Assert\IdenticalTo('US')])], then: [new Present(['zip']), new Property('zip', [new Assert\Regex('/^\d{5}$/')])], else: [new Property('zip', [new Assert\Length(max: 10)])])]
final class Shipment
{
    #[IfThenElse(if: [new Assert\Regex('/^\+/')], then: [new Assert\Length(min: 8)], else: [new Assert\Length(max: 6)])]
    public ?string $phone;

    private ?string $country;

    private ?string $zip;

    /**
     * @var array<string, string>
     */
    #[PatternProperty('/^x-/', [new Assert\Length(max: 3)])]
    private array $labels;

    /**
     * @param array<string, string> $labels
     */
    public function __construct(?string $country, ?string $zip, ?string $phone = null, array $labels = [])
    {
        $this->country = $country;
        $this->zip = $zip;
        $this->phone = $phone;
        $this->labels = $labels;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @return array<string, string>
     */
    public function getLabels(): array
    {
        return $this->labels;
    }
}
