<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\CollectionNormalizer;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Basket;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Numbers;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Opaque;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Tag;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\TagList;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
        $this->serializer = new Serializer([$this->normalizer, new ArrayDenormalizer(), $objects]);
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

    public function testReportsWhereAnItemIsWrong(): void
    {
        try {
            $this->serializer->denormalize(['tags' => [], 'prices' => [true]], Basket::class);
            self::fail('A boolean is no float.');
        } catch (NotNormalizableValueException $exception) {
            self::assertSame('prices[0]', $exception->getPath());
            self::assertSame(['float'], $exception->getExpectedTypes());
        }
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

    public function testRefusesAMalformedContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The context key dto_generator_collection must be {class: string, item: string}.');

        $this->normalizer->denormalize([], TagList::class, null, ['dto_generator_collection' => ['class' => TagList::class]]);
    }
}
