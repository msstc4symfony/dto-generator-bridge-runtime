<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime;

use ReflectionMethod;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Traversable;

/**
 * Reads a list into the collection object a generated DTO declares (dto-generator's `dto.collections`), and writes the
 * object as the list. The bridge names the class and the type of its items in the property's Context; the items go
 * through the Serializer, so they may be DTOs, enums or dates themselves.
 *
 * Built through a public static fromArray() when the class has one, else its constructor; read through toArray() when
 * it has one, else by iteration.
 *
 * Runs on PHP 8.0+ with symfony/serializer 5.4 to 8, but stays parseable by PHP 7.4, which lints the whole package.
 *
 * @api
 */
final class CollectionNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /** The context key: {class: the collection class, item: the Serializer type of an item, such as App\Tag, int or mixed}. */
    public const CONTEXT = 'dto_generator_collection';

    /** Checked here: the Serializer has no normalizer for builtin types. A float takes an int, as JSON does. */
    private const SCALARS = ['int' => 'is_int', 'float' => 'is_float', 'string' => 'is_string', 'bool' => 'is_bool'];

    private ?SerializerInterface $serializer = null;

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<mixed>
     *
     * @throws InvalidArgumentException when the object is neither readable through toArray() nor iterable
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if (is_object($data) && method_exists($data, 'toArray')) {
            $elements = $data->toArray();
        } elseif ($data instanceof Traversable) {
            $elements = iterator_to_array($data, false);
        } else {
            throw new InvalidArgumentException(sprintf('%s is neither readable through toArray() nor iterable.', is_object($data) ? get_class($data) : gettype($data)));
        }

        unset($context[self::CONTEXT]);
        $normalized = $this->normalizer()->normalize(is_array($elements) ? $elements : [], $format, $context);

        return is_array($normalized) ? array_values($normalized) : [];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        $collection = $this->collection($context);

        return $collection !== null && is_object($data) && get_class($data) === $collection[0];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException when the context does not name the class and the type of its items
     * @throws NotNormalizableValueException when the data is no list or an item is not of the type
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $collection = $this->collection($context);
        if ($collection === null) {
            throw new InvalidArgumentException(sprintf('The context key %s must be {class: string, item: string}.', self::CONTEXT));
        }

        $path = $context['deserialization_path'] ?? null;
        $path = is_string($path) ? $path : null;
        if (!is_array($data) || ($data !== [] && array_keys($data) !== range(0, count($data) - 1))) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The data of %s must be a list, an array with keys 0 to n-1.', $type), $data, ['array'], $path, true);
        }

        unset($context[self::CONTEXT]);
        $items = [];
        foreach ($data as $index => $value) {
            $items[] = $this->item($value, $collection[1], $type, $index, $format, $context, $path);
        }

        if (method_exists($type, 'fromArray') && (new ReflectionMethod($type, 'fromArray'))->isStatic() && (new ReflectionMethod($type, 'fromArray'))->isPublic()) {
            return $type::fromArray($items);
        }

        return new $type($items);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        $collection = $this->collection($context);

        return $collection !== null && $collection[0] === $type;
    }

    /**
     * Not cacheable: whether a value is a collection depends on the property's context.
     *
     * @return array{object: false}
     */
    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws NotNormalizableValueException
     */
    private function item(mixed $value, string $item, string $type, int $index, ?string $format, array $context, ?string $path): mixed
    {
        // An item the Serializer has no type for (a union, a list of scalars) stays as the data has it.
        if ($item === 'mixed') {
            return $value;
        }

        $at = sprintf('%s[%d]', $path ?? '', $index);
        $check = self::SCALARS[$item] ?? null;
        if ($check === null) {
            return $this->denormalizer()->denormalize($value, $item, $format, ['deserialization_path' => $at] + $context);
        }

        if ($item === 'float' && is_int($value)) {
            return (float) $value;
        }

        if (!$check($value)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('Item %d of %s must be %s, %s given.', $index, $type, $item, $this->typeOf($value)), $value, [$item], $at, true);
        }

        return $value;
    }

    /**
     * As get_debug_type() names it, which PHP 7.4 lacks (the package must load there beside the bridge).
     */
    private function typeOf(mixed $value): string
    {
        return is_object($value) ? get_class($value) : ['boolean' => 'bool', 'integer' => 'int', 'double' => 'float', 'NULL' => 'null'][gettype($value)] ?? gettype($value);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array{class-string, string}|null
     */
    private function collection(array $context): ?array
    {
        $collection = $context[self::CONTEXT] ?? null;
        if (!is_array($collection) || !is_string($collection['class'] ?? null) || !is_string($collection['item'] ?? null) || !class_exists($collection['class'])) {
            return null;
        }

        return [$collection['class'], $collection['item']];
    }

    private function normalizer(): NormalizerInterface
    {
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new LogicException(sprintf('%s needs a Serializer that normalizes.', self::class));
        }

        return $this->serializer;
    }

    private function denormalizer(): DenormalizerInterface
    {
        if (!$this->serializer instanceof DenormalizerInterface) {
            throw new LogicException(sprintf('%s needs a Serializer that denormalizes.', self::class));
        }

        return $this->serializer;
    }
}
