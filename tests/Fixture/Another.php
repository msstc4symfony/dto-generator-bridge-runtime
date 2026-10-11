<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

use Symfony\Component\Serializer\Attribute as Serializer;

#[Serializer\DiscriminatorMap(typeProperty: 'a', mapping: ['c' => Pair::class])]
interface Another
{
}
