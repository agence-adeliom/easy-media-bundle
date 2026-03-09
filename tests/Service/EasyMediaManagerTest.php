<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Service;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestFolder;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(\Adeliom\EasyMediaBundle\Service\EasyMediaManager::class)]
final class EasyMediaManagerTest extends TestCase
{
    public function testAccessorsDelegateToHelperAndRepositories(): void
    {
        $media = new TestMedia();
        $folder = new TestFolder();

        $folderRepository = $this->createMock(EntityRepository::class);
        $folderRepository->expects(self::once())->method('find')->with(12)->willReturn($folder);

        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->expects(self::once())->method('getPath')->with($media)->willReturn('gallery/hero-jpg');
        $helper->expects(self::once())->method('getFolderRepository')->willReturn($folderRepository);
        $helper->expects(self::once())->method('getMedia')->with(34)->willReturn($media);

        $filesystem = $this->createMock(FilesystemOperator::class);
        $manager = $this->createManager($filesystem, $helper);

        self::assertSame($filesystem, $manager->getFilesystem());
        self::assertSame($helper, $manager->getHelper());
        self::assertSame('gallery/hero-jpg', $manager->getPath($media));
        self::assertSame($folder, $manager->getFolder(12));
        self::assertSame($media, $manager->getMedia(34));
    }

    public function testPublicUrlUsesFilesystemPublicUrlAndNormalizesConfiguredBaseUrl(): void
    {
        $media = new TestMedia();
        $media->setName('Hero.jpg');

        $filesystemBuilder = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor();
        $filesystem = method_exists(FilesystemOperator::class, 'publicUrl')
            ? $filesystemBuilder->onlyMethods(['publicUrl'])->getMock()
            : $filesystemBuilder->addMethods(['publicUrl'])->getMock();
        $filesystem->expects(self::exactly(2))
            ->method('publicUrl')
            ->with('gallery/hero-jpg')
            ->willReturn('https://storage.example.com/uploads/gallery/hero-jpg');

        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->method('getPath')->with($media)->willReturn('gallery/hero-jpg');
        $helper->method('getBaseUrl')->willReturn('https://cdn.example.com/uploads');
        $helper->method('clearDblSlash')->willReturnCallback(static function (string $path): string {
            $path = preg_replace('#/+#', '/', $path);

            return str_replace(':/', '://', (string) $path);
        });

        $manager = $this->createManager($filesystem, $helper);

        self::assertSame('https://cdn.example.com/uploads/gallery/hero-jpg', $manager->publicUrl($media));
        self::assertSame('https://cdn.example.com/uploads/gallery/hero-jpg', $manager->downloadUrl($media));
    }

    public function testPublicUrlFallsBackToBaseUrlWhenFilesystemDoesNotSupportPublicUrls(): void
    {
        $media = new TestMedia();
        $media->setName('Hero.jpg');

        $filesystem = $this->createMock(FilesystemOperator::class);
        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->method('getPath')->with($media)->willReturn('gallery/hero-jpg');
        $helper->method('getBaseUrl')->willReturn('/media');
        $helper->method('clearDblSlash')->willReturnCallback(static fn (string $path): string => preg_replace('#/+#', '/', $path) ?? $path);

        $manager = $this->createManager($filesystem, $helper);

        self::assertSame('/media/gallery/hero-jpg', $manager->publicUrl($media));
    }

    public function testPublicUrlReturnsNullWhenMediaHasNoResolvablePath(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->method('getPath')->willReturn(null);

        $manager = $this->createManager($filesystem, $helper);

        self::assertNull($manager->publicUrl(new TestMedia()));
    }

    public function testFolderByPathReturnsNestedFolderWhenDirectoryExists(): void
    {
        $parent = new TestFolder();
        $parent->setName('Gallery');

        $child = new TestFolder();
        $child->setName('Summer');
        $child->setParent($parent);

        $folderRepository = $this->createMock(EntityRepository::class);
        $folderRepository->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(static function (array $criteria) use ($parent, $child): ?Folder {
                return match ($criteria['slug']) {
                    'gallery' => $criteria['parent'] === null ? $parent : null,
                    'summer' => $criteria['parent'] === $parent ? $child : null,
                    default => null,
                };
            });

        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())->method('directoryExists')->with('gallery/summer')->willReturn(true);

        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->expects(self::exactly(2))->method('getFolderRepository')->willReturn($folderRepository);

        $manager = $this->createManager($filesystem, $helper);

        self::assertSame($child, $manager->folderByPath('gallery/summer'));
        self::assertNull($manager->folderByPath(null));
    }

    public function testFolderByPathReturnsFalseWhenDirectoryDoesNotExist(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())->method('directoryExists')->with('missing/folder')->willReturn(false);

        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->expects(self::never())->method('getFolderRepository');

        $manager = $this->createManager($filesystem, $helper);

        self::assertFalse($manager->folderByPath('missing/folder'));
    }

    public function testSaveAndDeleteCoordinateEntityManagerAndFilesystem(): void
    {
        $folder = new TestFolder();
        $folder->setName('Gallery');

        $media = new TestMedia();
        $media->setName('Hero.jpg');
        $media->setFolder($folder);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persisted = [];
        $entityManager->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $removed = [];
        $entityManager->expects(self::exactly(2))
            ->method('remove')
            ->willReturnCallback(static function (object $entity) use (&$removed): void {
                $removed[] = $entity;
            });
        $entityManager->expects(self::exactly(2))->method('flush');

        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())->method('deleteDirectory')->with('gallery');
        $filesystem->expects(self::once())->method('delete')->with('gallery/hero-jpg');

        $manager = $this->createManager($filesystem, $this->createMock(EasyMediaHelper::class), $entityManager);

        $manager->save($folder);
        $manager->save($media, false);
        $manager->delete($folder);
        $manager->delete($media, false);

        self::assertSame([$folder, $media], $persisted);
        self::assertSame([$folder, $media], $removed);
    }

    public function testMoveOnlyCallsFilesystemWhenOriginExists(): void
    {
        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->method('clearDblSlash')->willReturnCallback(static fn (string $path): string => preg_replace('#/+#', '/', $path) ?? $path);

        $filesystem = $this->createMock(FilesystemOperator::class);
        $checkedFiles = [];
        $filesystem->expects(self::exactly(2))
            ->method('fileExists')
            ->willReturnCallback(static function (string $path) use (&$checkedFiles): bool {
                $checkedFiles[] = $path;

                return '/old/file.jpg' === $path;
            });
        $filesystem->expects(self::once())
            ->method('move')
            ->with('/old/file.jpg', '/new/file.jpg');
        $filesystem->expects(self::once())
            ->method('directoryExists')
            ->with('/old/folder')
            ->willReturn(false);

        $manager = $this->createManager($filesystem, $helper);

        $manager->move('//old//file.jpg', '//new//file.jpg');
        $manager->move('//old//folder', '//new//folder');

        self::assertSame(['/old/file.jpg', '/old/folder'], $checkedFiles);
    }

    private function createManager(FilesystemOperator $filesystem, EasyMediaHelper $helper, ?EntityManagerInterface $entityManager = null): EasyMediaManager
    {
        return new EasyMediaManager(
            $filesystem,
            $helper,
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $this->createMock(ContainerBagInterface::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );
    }
}
