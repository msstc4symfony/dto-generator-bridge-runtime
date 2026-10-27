<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit;

use ArrayObject;
use DateTimeImmutable;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\CollectionNormalizer;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Basket;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Guarded;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Hidden;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Instanced;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Keyed;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Numbers;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Opaque;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Pair;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Repeated;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\SubGuarded;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Tag;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\TagList;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Wrapped;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The normalizer in a Serializer built as FrameworkBundle builds it; the fixtures need the attributes of Symfony 6.4+.
 */
final class CollectionNormalizerTest extends TestCase
{
    private Serializer $serializer;

    private CollectionNormalizer $normalizer;

    protected function setUp(): void
    {
        if (!class_exists(Context::class) || !class_exists(AttributeLoader::class)) {
            self::markTestSkipped('The fixtures need the attributes of symfony/serializer 6.4 or newer.');
        }

        $metadata = new ClassMetadataFactory(new AttributeLoader());
        $types = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
        $objects = new ObjectNormalizer($metadata, new MetadataAwareNameConverter($metadata), null, $types, new ClassDiscriminatorFromClassMetadata($metadata));
        $this->normalizer = new CollectionNormalizer();
        $this->serializer = new Serializer([$this->normalizer, new DateTimeNormalizer(), new ArrayDenormalizer(), $objects]);
    }

    public function testReadsAPropertyIntoItsCollection(): void
    {
        $basket = $this->serializer->denormalize(['tags' => [['name' => 'red'], ['name' => 'blue']], 'prices' => [1.5, 2]], Basket::class);

        self::assertInstanceOf(Basket::class, $basket);
        self::assertSame(['red', 'blue'], array_map(static fn (Tag $tag): string => $tag->getName(), $basket->getTags()->toArray()));
        $prices = $basket->getPrices();
        self::assertInstanceOf(Numbers::class, $prices);
        self::assertSame([1.5, 2.0], iterator_to_array($prices, false));
    }

    public function testWritesACollectionAsAList(): void
    {
        $basket = new Basket(TagList::fromArray([new Tag('red')]), new Numbers([1.5]));

        self::assertSame(['tags' => [['name' => 'red']], 'prices' => [1.5]], $this->serializer->normalize($basket));
        self::assertSame(['tags' => [], 'prices' => null], $this->serializer->normalize(new Basket(TagList::fromArray([]))));
    }

    public function testKeepsANullCollection(): void
    {
        $basket = $this->serializer->denormalize(['tags' => [], 'prices' => null], Basket::class);

        self::assertInstanceOf(Basket::class, $basket);
        self::assertNull($basket->getPrices());
        self::assertSame([], $basket->getTags()->toArray());
    }

    /**
     * @dataProvider notLists
     *
     * @param array<string, mixed> $basket
     */
    public function testRejectsDataThatIsNoList(array $basket, string $message): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage($message);

        $this->serializer->denormalize($basket, Basket::class);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function notLists(): array
    {
        return [
            'map' => [['tags' => [], 'prices' => ['a' => 1.5]], 'The data of ' . Numbers::class . ' must be a list, an array with keys 0 to n-1.'],
            'string' => [['tags' => [], 'prices' => '1.5'], 'The data of ' . Numbers::class . ' must be a list, an array with keys 0 to n-1.'],
            'item' => [['tags' => [], 'prices' => [1.5, 'x']], 'Item 1 of ' . Numbers::class . ' must be float, string given.'],
        ];
    }

    public function testWritesEveryItemOfACollectionWhateverItsKeys(): void
    {
        self::assertSame([2, 1], $this->serializer->normalize(new Keyed(['b' => 2, 'a' => 1]), null, ['dto_generator_collection' => ['class' => Keyed::class, 'item' => 'int']]));
        self::assertSame([1, 2], $this->serializer->normalize(new Repeated([1, 2]), null, ['dto_generator_collection' => ['class' => Repeated::class, 'item' => 'int']]));
    }

    public function testReportsDataThatIsNoListToTheUser(): void
    {
        try {
            $this->serializer->denormalize(['tags' => [], 'prices' => '1.5'], Basket::class);
            self::fail('A string is no list.');
        } catch (NotNormalizableValueException $exception) {
            self::assertSame('prices', $exception->getPath());
            self::assertSame(['array'], $exception->getExpectedTypes());
            self::assertTrue($exception->canUseMessageForUser());
        }
    }

    public function testReportsWhereAnItemIsWrong(): void
    {
        try {
            $this->serializer->denormalize(['tags' => [], 'prices' => [true]], Basket::class);
            self::fail('A boolean is no float.');
        } catch (NotNormalizableValueException $exception) {
            self::assertSame('prices[0]', $exception->getPath());
            self::assertSame(['float'], $exception->getExpectedTypes());
            self::assertTrue($exception->canUseMessageForUser());
        }
    }

    public function testReportsWhereAPropertyOfAnItemIsWrong(): void
    {
        try {
            $this->serializer->denormalize(['tags' => [['name' => 'red'], ['name' => ['x']]]], Basket::class);
            self::fail('A list is no string.');
        } catch (NotNormalizableValueException $exception) {
            self::assertSame('tags[1].name', $exception->getPath());
        }
    }

    /**
     * @dataProvider wrongItems
     */
    public function testNamesTheTypeOfAWrongItem(mixed $item, string $given): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Item 0 of ' . Numbers::class . ' must be string, ' . $given . ' given.');

        $this->serializer->denormalize([$item], Numbers::class, null, ['dto_generator_collection' => ['class' => Numbers::class, 'item' => 'string']]);
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function wrongItems(): array
    {
        return [
            'bool' => [true, 'bool'],
            'int' => [1, 'int'],
            'float' => [1.5, 'float'],
            'null' => [null, 'null'],
            'array' => [[1], 'array'],
            'object' => [new Tag('red'), Tag::class],
        ];
    }

    public function testPassesItemsOfTypeMixedThrough(): void
    {
        $numbers = $this->serializer->denormalize([1, 'two', [3]], Numbers::class, null, ['dto_generator_collection' => ['class' => Numbers::class, 'item' => 'mixed']]);

        self::assertInstanceOf(Numbers::class, $numbers);
        self::assertSame([1, 'two', [3]], iterator_to_array($numbers, false));
    }

    public function testClaimsOnlyTheClassItsContextNames(): void
    {
        $context = ['dto_generator_collection' => ['class' => TagList::class, 'item' => Tag::class]];

        self::assertTrue($this->normalizer->supportsDenormalization([], TagList::class, null, $context));
        self::assertFalse($this->normalizer->supportsDenormalization([], Numbers::class, null, $context));
        self::assertFalse($this->normalizer->supportsDenormalization([], TagList::class));
        self::assertFalse($this->normalizer->supportsDenormalization([], TagList::class, null, ['dto_generator_collection' => 'x']));
        self::assertFalse($this->normalizer->supportsDenormalization([], TagList::class, null, ['dto_generator_collection' => new ArrayObject(['class' => TagList::class, 'item' => Tag::class])]));
        self::assertFalse($this->normalizer->supportsDenormalization([], TagList::class, null, ['dto_generator_collection' => ['class' => 1, 'item' => Tag::class]]));
        self::assertFalse($this->normalizer->supportsDenormalization([], TagList::class, null, ['dto_generator_collection' => ['class' => TagList::class, 'item' => 1]]));
        self::assertFalse($this->normalizer->supportsDenormalization([], 'App\\Missing', null, ['dto_generator_collection' => ['class' => 'App\\Missing', 'item' => Tag::class]]));
        self::assertTrue($this->normalizer->supportsNormalization(TagList::fromArray([]), null, $context));
        self::assertFalse($this->normalizer->supportsNormalization(new Numbers([]), null, $context));
        self::assertFalse($this->normalizer->supportsNormalization([], null, $context));
        self::assertFalse($this->normalizer->supportsNormalization(TagList::fromArray([])));
        self::assertSame(['object' => false], $this->normalizer->getSupportedTypes(null));
    }

    public function testRefusesACollectionItCannotReadAsAList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(Opaque::class . ' is neither readable through toArray() nor iterable.');

        $this->serializer->normalize(new Opaque(), null, ['dto_generator_collection' => ['class' => Opaque::class, 'item' => 'int']]);
    }

    /**
     * @dataProvider unbuildable
     *
     * @param class-string $class
     */
    public function testRefusesAClassItCannotBuildFromAList(string $class): void
    {
        try {
            $this->serializer->denormalize([1], $class, null, ['dto_generator_collection' => ['class' => $class, 'item' => 'int'], 'deserialization_path' => 'items']);
            self::fail('The class cannot be built.');
        } catch (NotNormalizableValueException $exception) {
            self::assertSame($class . ' has neither a public static fromArray() that returns it nor a public constructor that takes the list of items.', $exception->getMessage());
            self::assertSame([$class], $exception->getExpectedTypes());
            self::assertSame('items', $exception->getPath());
        }
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function unbuildable(): array
    {
        return ['no constructor' => [Opaque::class], 'two arguments' => [Pair::class], 'nothing public' => [Hidden::class]];
    }

    public function testBuildsThroughTheConstructorWhenFromArrayIsNoFactory(): void
    {
        $instanced = $this->serializer->denormalize([1, 2], Instanced::class, null, ['dto_generator_collection' => ['class' => Instanced::class, 'item' => 'int']]);

        self::assertInstanceOf(Instanced::class, $instanced);
        self::assertSame([1, 2], $instanced->items());
    }

    public function testReadsAndWritesItemsThroughTheSerializer(): void
    {
        $context = ['dto_generator_collection' => ['class' => Instanced::class, 'item' => DateTimeImmutable::class]];
        $dates = $this->serializer->denormalize(['2026-10-27T00:00:00+00:00'], Instanced::class, null, $context);

        self::assertInstanceOf(Instanced::class, $dates);
        $date = $dates->items()[0];
        self::assertInstanceOf(DateTimeImmutable::class, $date);
        self::assertSame('2026-10-27', $date->format('Y-m-d'));
        self::assertSame(['2026-10-27T00:00:00+00:00'], $this->serializer->normalize(new Numbers([$date]), null, ['dto_generator_collection' => ['class' => Numbers::class, 'item' => DateTimeImmutable::class]]));
    }

    public function testRefusesAnItemTypeTheSerializerCannotRead(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item type ?int of ' . Numbers::class . ' in the context key dto_generator_collection is no class, int, float, string, bool, mixed or list of them.');

        $this->serializer->denormalize([], Numbers::class, null, ['dto_generator_collection' => ['class' => Numbers::class, 'item' => '?int']]);
    }

    public function testReadsAListOfListsOfAClass(): void
    {
        $grid = $this->serializer->denormalize([[['name' => 'red']]], Instanced::class, null, ['dto_generator_collection' => ['class' => Instanced::class, 'item' => Tag::class . '[]']]);

        self::assertInstanceOf(Instanced::class, $grid);
        $row = $grid->items()[0];
        self::assertIsArray($row);
        self::assertInstanceOf(Tag::class, $row[0]);
    }

    public function testCollectsTheErrorsOfItemsWithTheOthers(): void
    {
        try {
            $this->serializer->denormalize(['tags' => [['name' => 'red']], 'prices' => [1.5, 'x']], Basket::class, null, ['collect_denormalization_errors' => true]);
            self::fail('A string is no float.');
        } catch (PartialDenormalizationException $exception) {
            self::assertSame(['prices[1]'], array_map(static fn (NotNormalizableValueException $error): ?string => $error->getPath(), $exception->getErrors()));
        }
    }

    public function testReadsAnIterableToArrayAndIteratesAClassWhoseToArrayIsHidden(): void
    {
        self::assertSame([1, 2], $this->serializer->normalize(new Wrapped([1, 2]), null, ['dto_generator_collection' => ['class' => Wrapped::class, 'item' => 'int']]));
        self::assertSame([3], $this->serializer->normalize(new Guarded([3]), null, ['dto_generator_collection' => ['class' => Guarded::class, 'item' => 'int']]));
    }

    public function testWritesASubclassOfTheCollectionClass(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(new SubGuarded([1]), null, ['dto_generator_collection' => ['class' => Guarded::class, 'item' => 'int']]));
        self::assertFalse($this->normalizer->supportsDenormalization([1], SubGuarded::class, null, ['dto_generator_collection' => ['class' => Guarded::class, 'item' => 'int']]));
    }

    public function testNeedsASerializer(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(CollectionNormalizer::class . ' needs a Serializer that normalizes.');

        (new CollectionNormalizer())->normalize(new Numbers([1.5]));
    }

    public function testNeedsASerializerThatDenormalizes(): void
    {
        $normalizer = new CollectionNormalizer();
        $normalizer->setSerializer(new class implements SerializerInterface {
            public function serialize(mixed $data, string $format, array $context = []): string
            {
                return '';
            }

            public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
            {
                return null;
            }
        });

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(CollectionNormalizer::class . ' needs a Serializer that denormalizes.');

        $normalizer->denormalize([['name' => 'red']], TagList::class, null, ['dto_generator_collection' => ['class' => TagList::class, 'item' => Tag::class]]);
    }

    public function testRefusesAMalformedContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The context key dto_generator_collection must be {class: string, item: string}.');

        $this->normalizer->denormalize([], TagList::class, null, ['dto_generator_collection' => ['class' => TagList::class]]);
    }
}
