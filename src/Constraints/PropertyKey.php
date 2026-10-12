<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints;

use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Where a property of an object sits in its array cast, which reaches protected and private properties, also of
 * parents, without ReflectionProperty::setAccessible(): PHP 7.4 needs that call, and PHP 8.5 deprecates it.
 *
 * @internal
 */
final class PropertyKey
{
    private function __construct()
    {
    }

    /**
     * @param array<array-key, mixed> $values the object as an array: "\0Class\0name" for a private property of Class,
     *                                        "\0*\0name" for a protected one; mixed, as properties hold anything
     *
     * @return string|null null when the property is uninitialized
     *
     * @throws ConstraintDefinitionException when the class has no such property
     */
    public static function find(array $values, object $object, string $property): ?string
    {
        $classes = [];
        for ($class = get_class($object); $class !== false; $class = get_parent_class($class)) {
            $classes[] = $class;
        }

        // A protected or public property excludes a private one of the same name in the class, not in a parent: the
        // parents' private ones come last, nearest first.
        $private = array_map(static fn (string $class): string => "\0" . $class . "\0" . $property, $classes);
        $keys = array_merge(["\0*\0" . $property, $property], $private);
        foreach ($keys as $key) {
            if (array_key_exists($key, $values)) {
                return $key;
            }
        }

        foreach ($classes as $class) {
            if (property_exists($class, $property)) {
                return null;
            }
        }

        throw new ConstraintDefinitionException(sprintf('The class "%s" has no property "%s".', get_class($object), $property));
    }

    /**
     * Whether the property is initialized and not null: a DTO cannot tell a missing key from null.
     *
     * @param array<array-key, mixed> $values the object as an array
     */
    public static function isSet(array $values, object $object, string $property): bool
    {
        $key = self::find($values, $object, $property);

        return $key !== null && $values[$key] !== null;
    }
}
