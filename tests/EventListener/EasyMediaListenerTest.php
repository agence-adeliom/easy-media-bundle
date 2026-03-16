<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\EventListener;

use Adeliom\EasyMediaBundle\EventListener\DoctrineMappingListener;
use Adeliom\EasyMediaBundle\EventListener\FolderSubscriber;
use Adeliom\EasyMediaBundle\EventListener\MediaSubscriber;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestFolder;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Adeliom\EasyMediaBundle\EventListener\DoctrineMappingListener::class)]
#[CoversClass(\Adeliom\EasyMediaBundle\EventListener\FolderSubscriber::class)]
#[CoversClass(\Adeliom\EasyMediaBundle\EventListener\MediaSubscriber::class)]
final class EasyMediaListenerTest extends TestCase
{
    public function testDoctrineMappingListenerMapsFolderRelationsWhenMissing(): void
    {
        $metadata = new ClassMetadata(TestFolder::class);
        $listener = new DoctrineMappingListener(TestMedia::class, TestFolder::class);
        $listener->loadClassMetadata(new LoadClassMetadataEventArgs($metadata, $this->createMock(EntityManagerInterface::class)));

        $parentMapping = $metadata->getAssociationMapping('parent');
        $childrenMapping = $metadata->getAssociationMapping('children');
        $mediasMapping = $metadata->getAssociationMapping('medias');

        self::assertSame(TestFolder::class, $parentMapping->targetEntity);
        self::assertSame('children', $parentMapping->inversedBy);
        self::assertTrue($parentMapping->joinColumns[0]->nullable);
        self::assertSame('SET NULL', $parentMapping->joinColumns[0]->onDelete);
        self::assertSame(TestFolder::class, $childrenMapping->targetEntity);
        self::assertSame('parent', $childrenMapping->mappedBy);
        self::assertSame(TestMedia::class, $mediasMapping->targetEntity);
        self::assertSame('folder', $mediasMapping->mappedBy);
    }

    public function testDoctrineMappingListenerMapsMediaFolderRelationWhenMissing(): void
    {
        $metadata = new ClassMetadata(TestMedia::class);
        $listener = new DoctrineMappingListener(TestMedia::class, TestFolder::class);
        $listener->loadClassMetadata(new LoadClassMetadataEventArgs($metadata, $this->createMock(EntityManagerInterface::class)));

        $mapping = $metadata->getAssociationMapping('folder');

        self::assertSame('folder', $mapping->fieldName);
        self::assertSame(TestFolder::class, $mapping->targetEntity);
        self::assertSame('medias', $mapping->inversedBy);
    }

    public function testDoctrineMappingListenerSkipsExistingAssociations(): void
    {
        $listener = new DoctrineMappingListener(TestMedia::class, TestFolder::class);
        $metadata = new ClassMetadata(TestFolder::class);
        $event = new LoadClassMetadataEventArgs($metadata, $this->createMock(EntityManagerInterface::class));

        $listener->loadClassMetadata($event);
        $listener->loadClassMetadata($event);

        self::assertCount(3, $metadata->getAssociationMappings());
    }

    public function testFolderSubscriberMovesFolderWhenParentOrSlugChanges(): void
    {
        $oldParent = new TestFolder();
        $oldParent->setName('Old Parent');

        $newParent = new TestFolder();
        $newParent->setName('New Parent');

        $folder = new TestFolder();
        $folder->setName('Child Folder');
        $folder->setParent($newParent);
        $folder->setSlug('child-folder-updated');

        $changes = [
            'parent' => [$oldParent, $newParent],
            'slug' => ['child-folder-old', 'child-folder-updated'],
        ];

        $manager = $this->createMock(EasyMediaManager::class);
        $moves = [];
        $manager->expects(self::exactly(2))
            ->method('move')
            ->willReturnCallback(static function (string $from, string $to) use (&$moves): void {
                $moves[] = [$from, $to];
            });

        $subscriber = new FolderSubscriber($manager);
        $subscriber->preUpdate(new PreUpdateEventArgs($folder, $this->createMock(EntityManagerInterface::class), $changes));

        self::assertSame([
            ['old-parent/child-folder-updated', 'new-parent/child-folder-updated'],
            ['child-folder-updated/child-folder-old', 'child-folder-updated/child-folder-updated'],
        ], $moves);
    }

    public function testFolderSubscriberIgnoresUnsupportedObjects(): void
    {
        $manager = $this->createMock(EasyMediaManager::class);
        $manager->expects(self::never())->method('move');

        $changes = ['parent' => [null, null]];
        $subscriber = new FolderSubscriber($manager);
        $subscriber->preUpdate(new PreUpdateEventArgs(new \stdClass(), $this->createMock(EntityManagerInterface::class), $changes));
    }

    public function testMediaSubscriberMovesMediaWhenFolderChanges(): void
    {
        $oldFolder = new TestFolder();
        $oldFolder->setName('Old Gallery');

        $newFolder = new TestFolder();
        $newFolder->setName('New Gallery');

        $media = new TestMedia();
        $media->setName('Hero.jpg');

        $changes = [
            'folder' => [$oldFolder, $newFolder],
        ];

        $manager = $this->createMock(EasyMediaManager::class);
        $manager->expects(self::once())
            ->method('move')
            ->with('old-gallery/hero-jpg', 'new-gallery/hero-jpg');

        $subscriber = new MediaSubscriber($manager);
        $subscriber->preUpdate(new PreUpdateEventArgs($media, $this->createMock(EntityManagerInterface::class), $changes));
    }

    public function testMediaSubscriberIgnoresUnsupportedObjects(): void
    {
        $manager = $this->createMock(EasyMediaManager::class);
        $manager->expects(self::never())->method('move');

        $changes = ['folder' => [null, null]];
        $subscriber = new MediaSubscriber($manager);
        $subscriber->preUpdate(new PreUpdateEventArgs(new \stdClass(), $this->createMock(EntityManagerInterface::class), $changes));
    }
}
