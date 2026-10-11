<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

use Symfony\Component\Serializer\Attribute as Serializer;

#[Serializer\DiscriminatorMap(typeProperty: 'type', mapping: ['circle' => Circle::class])]
interface Shape
{
}
