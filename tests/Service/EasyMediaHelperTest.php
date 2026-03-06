<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Service;

use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestFolder;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Routing\RouterInterface;

#[CoversClass(\Adeliom\EasyMediaBundle\Service\EasyMediaHelper::class)]
final class EasyMediaHelperTest extends TestCase
{
    public function testAccessorsRepositoriesAndCleanNameFallbackUseConfiguredParameters(): void
    {
        $folderRepository = $this->createMock(EntityRepository::class);
        $mediaRepository = $this->createMock(EntityRepository::class);
        $helper = $this->createHelper(
            [
                'easy_media.folder_entity' => TestFolder::class,
                'easy_media.media_entity' => TestMedia::class,
                'easy_media.base_url' => 'https://cdn.example.test/media',
                'easy_media.sanitized_text' => static fn (): string => 'random-token',
                'easy_media.allowed_fileNames_chars' => '\.',
                'easy_media.allowed_folderNames_chars' => '\-',
            ],
            $folderRepository,
            $mediaRepository
        );

        self::assertSame(TestFolder::class, $helper->getFolderClassName());
        self::assertSame(TestMedia::class, $helper->getMediaClassName());
        self::assertSame($folderRepository, $helper->getFolderRepository());
        self::assertSame($mediaRepository, $helper->getMediaRepository());
        self::assertSame('https://cdn.example.test/media', $helper->getBaseUrl());
        self::assertSame('random-token', $helper->getRandomString());
        self::assertSame('Heroimage.jpg', $helper->cleanName('Hero image.jpg'));
        self::assertSame('random-token', $helper->cleanName('@@@'));
        self::assertSame('Gallery-folder', $helper->cleanName('Gallery-folder', true));
    }

    public function testGetItemTimeFormatsTimestampsAndNull(): void
    {
        $helper = $this->createHelper([
            'easy_media.last_modified_format' => 'Y-m-d H:i',
        ]);

        self::assertSame('2023-11-14 22:13', $helper->getItemTime(1700000000));
        self::assertNull($helper->getItemTime(null));
    }

    public function testGetMediaAndGetPathResolveIdentifiersAndManagedEntities(): void
    {
        $identifierMedia = new TestMedia();
        $identifierMedia->setName('Identifier.jpg');
        $identifierMedia->setSlug('identifier-jpg');
        $this->setId($identifierMedia, 15);

        $managedMedia = new TestMedia();
        $managedMedia->setName('Managed.jpg');
        $managedMedia->setSlug('managed-jpg');
        $this->setId($managedMedia, 42);

        $mediaRepository = $this->createMock(EntityRepository::class);
        $mediaRepository->expects(self::exactly(4))
            ->method('find')
            ->willReturnCallback(static function (mixed $value) use ($identifierMedia, $managedMedia): ?TestMedia {
                return match ($value) {
                    '15' => $identifierMedia,
                    15 => $identifierMedia,
                    42 => $managedMedia,
                    default => null,
                };
            });

        $helper = $this->createHelper(
            [
                'easy_media.media_entity' => TestMedia::class,
            ],
            null,
            $mediaRepository
        );

        self::assertSame($identifierMedia, $helper->getMedia('15'));
        self::assertSame($managedMedia, $helper->getMedia($managedMedia));
        self::assertSame('managed-jpg', $helper->getPath($managedMedia));
    }

    public function testGetMediaRejectsUnexpectedObjectType(): void
    {
        $helper = $this->createHelper([
            'easy_media.media_entity' => TestMedia::class,
        ]);

        $this->expectException(\TypeError::class);

        $helper->getMedia(new \stdClass());
    }

    public function testGetMediaAndPathReturnNullWhenRepositoryFails(): void
    {
        $mediaRepository = $this->createMock(EntityRepository::class);
        $mediaRepository->method('find')->willThrowException(new \RuntimeException('broken repository'));

        $helper = $this->createHelper(
            [
                'easy_media.media_entity' => TestMedia::class,
            ],
            null,
            $mediaRepository
        );

        self::assertNull($helper->getMedia('18'));
        self::assertNull($helper->getPath('18'));
    }

    public function testUtilityMethodsExposeMimeIconAndTypeHelpers(): void
    {
        $media = new TestMedia();
        $media->setMime('application/vnd.rar');

        $helper = $this->createHelper([
            'easy_media.extended_mimes' => [
                'image' => ['image/x-custom'],
                'video' => ['video/x-custom'],
                'audio' => ['audio/x-custom'],
                'archive' => ['application/vnd.rar'],
            ],
        ]);

        self::assertSame('https://example.test/a/b', $helper->clearDblSlash('https://example.test//a///b'));
        self::assertSame('jpeg', EasyMediaHelper::mime2ext('image/jpeg'));
        self::assertFalse(EasyMediaHelper::mime2ext('application/unknown'));
        self::assertSame('fa-file-image-o', EasyMediaHelper::mime2icon('image/svg+xml'));
        self::assertSame('fa-file-o', EasyMediaHelper::mime2icon('application/x-custom'));
        self::assertTrue($helper->fileIsType('image/x-custom', 'image'));
        self::assertTrue($helper->fileIsType($media, 'archive'));
        self::assertFalse($helper->fileIsType('application/pdf', 'archive'));
        self::assertFalse($helper->fileIsType('', 'image'));
    }

    private function createHelper(
        array $parameters,
        ?EntityRepository $folderRepository = null,
        ?EntityRepository $mediaRepository = null,
    ): EasyMediaHelper {
        $parameterBag = $this->createMock(ContainerBagInterface::class);
        $parameterBag->method('get')
            ->willReturnCallback(static fn (string $name): mixed => $parameters[$name] ?? throw new \InvalidArgumentException('Unknown parameter '.$name));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturnCallback(static function (string $class) use ($parameters, $folderRepository, $mediaRepository): EntityRepository {
                return match ($class) {
                    $parameters['easy_media.folder_entity'] ?? '__missing_folder__' => $folderRepository ?? throw new \InvalidArgumentException('Missing folder repository'),
                    $parameters['easy_media.media_entity'] ?? '__missing_media__' => $mediaRepository ?? throw new \InvalidArgumentException('Missing media repository'),
                    default => throw new \InvalidArgumentException('Unknown repository '.$class),
                };
            });

        return new EasyMediaHelper(
            $parameterBag,
            $entityManager,
            $this->createMock(RouterInterface::class)
        );
    }

    private function setId(TestMedia $media, int $id): void
    {
        $property = new \ReflectionProperty($media, 'id');
        $property->setValue($media, $id);
    }
}
