<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Entity;

use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestFolder;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Adeliom\EasyMediaBundle\Entity\Media::class)]
final class MediaTest extends TestCase
{
    public function testMediaBuildsSlugPathAndMetadata(): void
    {
        $folder = new TestFolder();
        $folder->setName('Gallery');

        $media = new TestMedia();
        $media->setName('Image Hero.jpg');
        $media->setMime('image/jpeg');
        $media->setSize(128);
        $media->setLastModified(1234567890);
        $media->setMetas(['alt' => 'Hero image']);
        $media->setFolder($folder);

        self::assertSame('image-hero-jpg', $media->getSlug());
        self::assertSame('image/jpeg', $media->getMime());
        self::assertSame(128, $media->getSize());
        self::assertSame(1234567890, $media->getLastModified());
        self::assertSame(['alt' => 'Hero image'], $media->getMetas());
        self::assertSame('Hero image', $media->getMeta('alt'));
        self::assertSame('gallery/image-hero-jpg', $media->getPath());
    }

    public function testMediaStringRepresentationUsesIdentifier(): void
    {
        $media = new TestMedia();
        $property = new \ReflectionProperty($media, 'id');
        $property->setValue($media, 42);

        self::assertSame('42', (string) $media);
    }
}
