<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Types;

use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use Adeliom\EasyMediaBundle\Types\EasyMediaType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Adeliom\EasyMediaBundle\Types\EasyMediaType::class)]
final class EasyMediaTypeTest extends TestCase
{
    protected function tearDown(): void
    {
        EasyMediaType::setMediaResolver(null);
    }

    public function testTypeResolvesPhpValuesAndGracefullyHandlesFailures(): void
    {
        EasyMediaType::setMediaResolver(static fn (mixed $value): string => 'media-'.$value);
        $type = new EasyMediaType();
        $platform = $this->createMock(AbstractPlatform::class);

        self::assertSame('media-12', $type->convertToPHPValue(12, $platform));
        self::assertNull($type->convertToPHPValue('', $platform));

        EasyMediaType::setMediaResolver(static function (): never {
            throw new \RuntimeException('Resolver failure');
        });

        self::assertNull($type->convertToPHPValue(12, $platform));

        EasyMediaType::setMediaResolver(null);

        self::assertNull($type->convertToPHPValue(12, $platform));
    }

    public function testTypeConvertsDatabaseValueMetadataAndMediaInstances(): void
    {
        $media = new TestMedia();
        $property = new \ReflectionProperty($media, 'id');
        $property->setValue($media, 21);

        $type = new EasyMediaType();
        $platform = $this->createMock(AbstractPlatform::class);

        self::assertSame('TEXT', $type->getSQLDeclaration([], $platform));
        self::assertSame(21, $type->convertToDatabaseValue($media, $platform));
        self::assertSame('42', $type->convertToDatabaseValue('42', $platform));
        self::assertNull($type->convertToDatabaseValue(null, $platform));
        self::assertSame(EasyMediaType::EASYMEDIATYPE, $type->getName());
    }
}
