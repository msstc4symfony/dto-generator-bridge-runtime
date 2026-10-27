<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime;

use Closure;
use ReflectionClass;
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
 * Built through a public static fromArray() of the list when the class has one, else a public constructor of it; an
 * abstract class is not built. Read through a public, non-static toArray() without required parameters when it has
 * one, else by iteration. A collection refuses items with an \InvalidArgumentException, which reaches the Serializer
 * as data it cannot read; any other throwable is the class's own failure and passes as it is. Lists come as JSON gives
 * them: XML and CSV, which give a single item or a string, are not read.
 *
 * Runs on PHP 8.0+ with symfony/serializer 5.4 to 8, but stays parseable by PHP 7.4, which lints the whole package.
 *
 * @phpstan-type Collection array{class: class-string, item: non-empty-string}
 *
 * @api
 */
final class CollectionNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /** The context key: {class: the collection class, item: the Serializer type of an item, such as App\Tag, int or mixed}. */
    public const CONTEXT = 'dto_generator_collection';

    /** Checked here: the Serializer has no normalizer for builtin types. A float takes an int, as JSON does. */
    private const SCALARS = ['int' => 'is_int', 'float' => 'is_float', 'string' => 'is_string', 'bool' => 'is_bool'];

    /** gettype() names as get_debug_type() gives them; phpstan-php74.neon, which checks all of src, bans the function. */
    private const TYPE_NAMES = ['boolean' => 'bool', 'integer' => 'int', 'double' => 'float', 'NULL' => 'null'];

    /** The parameter types that take the list. */
    private const LIST_TYPES = ['array', 'iterable', 'mixed'];

    private ?SerializerInterface $serializer = null;

    /** @var array<class-string, (Closure(list<mixed>): mixed)|null> how each class is built, found once */
    private array $builders = [];

    /** @var array<class-string, ReflectionMethod|null> the toArray() each class is read through, found once */
    private array $readers = [];

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
     * @throws LogicException when the Serializer gives no array for the items
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $reader = is_object($data) ? $this->reader(get_class($data)) : null;
        $elements = $reader instanceof ReflectionMethod ? $reader->invoke($data) : $data;
        if (!is_array($elements) && !$elements instanceof Traversable) {
            throw new InvalidArgumentException(sprintf('%s is neither readable through toArray() nor iterable.', is_object($data) ? get_class($data) : gettype($data)));
        }

        $items = is_array($elements) ? $elements : iterator_to_array($elements, false);
        // empty_array_as_object makes the Serializer write [] as an ArrayObject.
        if ($items === []) {
            return [];
        }

        unset($context[self::CONTEXT]);
        $normalized = $this->normalizer()->normalize($items, $format, $context);
        if (!is_array($normalized)) {
            throw new LogicException(sprintf('The Serializer normalized a list into %s.', $this->typeOf($normalized)));
        }

        return array_values($normalized);
    }

    /**
     * A subclass of the collection class is one too.
     *
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        $collection = $this->collection($context);

        return $collection !== null && $data instanceof $collection['class'];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException when the context does not name the class and a type the Serializer reads items as
     * @throws NotNormalizableValueException when the data is no list, an item is not of the type, or the class cannot be
     *                                       built from the list or refuses it
     * @throws LogicException when the Serializer does not denormalize
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $collection = $this->collection($context);
        if ($collection === null) {
            throw new InvalidArgumentException(sprintf('The context key %s must be {class: string, item: string}.', self::CONTEXT));
        }

        if (!$this->readable($collection['item'])) {
            throw new InvalidArgumentException(sprintf('The item type %s of %s in the context key %s is no class, int, float, string, bool, mixed or list of classes.', $collection['item'], $type, self::CONTEXT));
        }

        $path = $context['deserialization_path'] ?? null;
        $path = is_string($path) ? $path : null;
        if (!is_array($data) || ($data !== [] && array_keys($data) !== range(0, count($data) - 1))) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The data of %s must be a list, an array with keys 0 to n-1.', $type), $data, ['array'], $path, true);
        }

        unset($context[self::CONTEXT]);
        $items = [];
        foreach ($data as $index => $value) {
            $at = sprintf('%s[%d]', $path ?? '', $index);
            $items[] = $this->item($value, $collection['item'], sprintf('Item %d of %s', $index, $type), $at, $format, $context);
        }

        return $this->build($collection['class'], $items, $path);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        $collection = $this->collection($context);

        return $collection !== null && $collection['class'] === $type;
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
     * Whether the Serializer reads an item as the type: a class, a scalar this class checks, mixed, or a list of classes
     * (ArrayDenormalizer has no type for a list of scalars).
     */
    private function readable(string $item): bool
    {
        $class = $item;
        while (substr($class, -2) === '[]') {
            $class = substr($class, 0, -2);
        }

        return ($class === $item && (isset(self::SCALARS[$item]) || $item === 'mixed')) || class_exists($class) || interface_exists($class);
    }

    /**
     * @param class-string $class
     * @param list<mixed> $items
     *
     * @throws NotNormalizableValueException when the class has no way to be built from the list, or refuses it
     */
    private function build(string $class, array $items, ?string $path): object
    {
        $builder = array_key_exists($class, $this->builders) ? $this->builders[$class] : $this->builders[$class] = $this->builder($class);
        if (!$builder instanceof Closure) {
            $message = (new ReflectionClass($class))->isAbstract() ? '%s is abstract, so no collection of it is built.' : '%s has neither a public static fromArray() nor a public constructor that takes the list of items.';

            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf($message, $class), $items, [$class], $path);
        }

        // A collection that checks its items refuses them as dto-collection's do, with an InvalidArgumentException; any
        // other failure is the class's bug, not the data's.
        try {
            $collection = $builder($items);
        } catch (\InvalidArgumentException $exception) {
            throw NotNormalizableValueException::createForUnexpectedDataType($exception->getMessage(), $items, [$class], $path, false, 0, $exception);
        }

        if (!$collection instanceof $class) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('%s::fromArray() gave %s, not the collection.', $class, $this->typeOf($collection)), $items, [$class], $path);
        }

        return $collection;
    }

    /**
     * A public static fromArray() of the list, or a public constructor of it; either may take further optional
     * parameters.
     *
     * @param class-string $class
     *
     * @return (Closure(list<mixed>): mixed)|null
     */
    private function builder(string $class): ?Closure
    {
        // An abstract class is no collection to build, whatever its factory would give.
        $reflection = new ReflectionClass($class);
        if ($reflection->isAbstract()) {
            return null;
        }

        if ($reflection->hasMethod('fromArray')) {
            $factory = $reflection->getMethod('fromArray');
            if ($factory->isStatic() && $factory->isPublic() && $this->takesTheList($factory)) {
                return static fn (array $items): mixed => $factory->invoke(null, $items);
            }
        }

        $constructor = $reflection->getConstructor();
        if ($reflection->isInstantiable() && $constructor instanceof ReflectionMethod && $this->takesTheList($constructor)) {
            return static fn (array $items): object => $reflection->newInstance($items);
        }

        return null;
    }

    /**
     * The public toArray() the class is read through, called without arguments; __call makes any name callable, so the
     * method itself is asked.
     *
     * @param class-string $class
     */
    private function reader(string $class): ?ReflectionMethod
    {
        if (!array_key_exists($class, $this->readers)) {
            $method = method_exists($class, 'toArray') ? new ReflectionMethod($class, 'toArray') : null;
            $this->readers[$class] = $method instanceof ReflectionMethod && $method->isPublic() && !$method->isStatic() && $method->getNumberOfRequiredParameters() === 0 ? $method : null;
        }

        return $this->readers[$class];
    }

    /**
     * Whether the list goes as the first argument and the only required one: a variadic parameter takes the items one by
     * one.
     */
    private function takesTheList(ReflectionMethod $method): bool
    {
        $parameters = $method->getParameters();
        if ($parameters === [] || $parameters[0]->isVariadic() || $parameters[0]->isPassedByReference() || $method->getNumberOfRequiredParameters() > 1) {
            return false;
        }

        // A union type reads "array|string"; ReflectionUnionType is no class on PHP 7.4, which loads the package.
        $type = $parameters[0]->getType();
        $names = $type === null ? ['mixed'] : explode('|', ltrim((string) $type, '?'));

        return array_intersect($names, self::LIST_TYPES) !== [];
    }

    /**
     * @param array<string, mixed> $context
     * @param non-empty-string $label how a message names the item
     * @param non-empty-string $at the item's path
     *
     * @throws NotNormalizableValueException
     */
    private function item(mixed $value, string $item, string $label, string $at, ?string $format, array $context): mixed
    {
        // An item the Serializer has no type for (a union, a list of scalars) stays as the data has it.
        if ($item === 'mixed') {
            return $value;
        }

        // ArrayDenormalizer refuses anything but an array for a list, and tells no path.
        if (substr($item, -2) === '[]' && !is_array($value)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('%s must be a list, %s given.', $label, $this->typeOf($value)), $value, ['array'], $at, true);
        }

        $check = self::SCALARS[$item] ?? null;
        if ($check === null) {
            return $this->denormalizer()->denormalize($value, $item, $format, ['deserialization_path' => $at] + $context);
        }

        if ($item === 'float' && is_int($value)) {
            return (float) $value;
        }

        if (!$check($value)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('%s must be %s, %s given.', $label, $item, $this->typeOf($value)), $value, [$item], $at, true);
        }

        return $value;
    }

    /**
     * @return non-empty-string
     */
    private function typeOf(mixed $value): string
    {
        return is_object($value) ? get_class($value) : self::TYPE_NAMES[gettype($value)] ?? gettype($value);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return Collection|null
     */
    private function collection(array $context): ?array
    {
        $collection = $context[self::CONTEXT] ?? null;
        if (!is_array($collection)) {
            return null;
        }

        $class = $collection['class'] ?? null;
        $item = $collection['item'] ?? null;
        if (!is_string($class) || !class_exists($class) || !is_string($item) || $item === '') {
            return null;
        }

        return ['class' => $class, 'item' => $item];
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
