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
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')->willReturn(TestFolder::class);
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->expects(self::once())
            ->method('mapManyToOne')
            ->with(self::arrayHasKey('fieldName'));
        $metadata->expects(self::exactly(2))
            ->method('mapOneToMany')
            ->with(self::callback(static fn (array $mapping): bool => \in_array($mapping['fieldName'], ['children', 'medias'], true)));

        $listener = new DoctrineMappingListener(TestMedia::class, TestFolder::class);
        $listener->loadClassMetadata(new LoadClassMetadataEventArgs($metadata, $this->createMock(EntityManagerInterface::class)));
    }

    public function testDoctrineMappingListenerMapsMediaFolderRelationWhenMissing(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')->willReturn(TestMedia::class);
        $metadata->method('hasAssociation')->with('folder')->willReturn(false);
        $metadata->expects(self::once())
            ->method('mapManyToOne')
            ->with(self::callback(static fn (array $mapping): bool => 'folder' === $mapping['fieldName'] && TestFolder::class === $mapping['targetEntity']));
        $metadata->expects(self::never())->method('mapOneToMany');

        $listener = new DoctrineMappingListener(TestMedia::class, TestFolder::class);
        $listener->loadClassMetadata(new LoadClassMetadataEventArgs($metadata, $this->createMock(EntityManagerInterface::class)));
    }

    public function testDoctrineMappingListenerSkipsExistingAssociations(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')->willReturn(TestFolder::class);
        $metadata->method('hasAssociation')->willReturn(true);
        $metadata->expects(self::never())->method('mapManyToOne');
        $metadata->expects(self::never())->method('mapOneToMany');

        $listener = new DoctrineMappingListener(TestMedia::class, TestFolder::class);
        $listener->loadClassMetadata(new LoadClassMetadataEventArgs($metadata, $this->createMock(EntityManagerInterface::class)));
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
