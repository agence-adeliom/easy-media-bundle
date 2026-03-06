<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Entity;

use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestFolder;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Adeliom\EasyMediaBundle\Entity\Folder::class)]
final class FolderTest extends TestCase
{
    public function testFolderBuildsTreePathAndRelations(): void
    {
        $root = new TestFolder();
        $root->setName('Root Folder');

        $child = new TestFolder();
        $child->setName('Child Folder');
        $root->addChild($child);

        $media = new TestMedia();
        $media->setName('Image Hero.jpg');
        $child->addMedia($media);

        self::assertSame('root-folder', $root->getSlug());
        self::assertSame('child-folder', $child->getSlug());
        self::assertSame('root-folder/child-folder', $child->getPath());
        self::assertSame($root, $child->getParent());
        self::assertCount(1, $root->getChildren());
        self::assertCount(1, $child->getMedias());
        self::assertSame($child, $media->getFolder());
    }
}
