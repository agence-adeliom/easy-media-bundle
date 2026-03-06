<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Imagine\Data;

use Adeliom\EasyMediaBundle\Imagine\Data\EasyMediaDataLoader;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\MimeTypesInterface;

#[CoversClass(\Adeliom\EasyMediaBundle\Imagine\Data\EasyMediaDataLoader::class)]
final class EasyMediaDataLoaderTest extends TestCase
{
    public function testFindBuildsBinaryFromFilesystemContents(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->method('mimeType')->with('gallery/hero.jpg')->willReturn('image/jpeg');
        $filesystem->method('read')->with('gallery/hero.jpg')->willReturn('binary-content');

        $mimeTypes = $this->createMock(MimeTypesInterface::class);
        $mimeTypes->method('getExtensions')->with('image/jpeg')->willReturn(['jpg']);

        $loader = new EasyMediaDataLoader($filesystem, $mimeTypes);
        $binary = $loader->find('gallery/hero.jpg');

        self::assertSame('binary-content', $binary->getContent());
        self::assertSame('image/jpeg', $binary->getMimeType());
        self::assertSame('jpg', $binary->getFormat());
    }

    public function testFindWrapsFilesystemFailures(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->method('mimeType')->willThrowException(new class('not found') extends \RuntimeException implements FilesystemException {
        });

        $loader = new EasyMediaDataLoader($filesystem, $this->createMock(MimeTypesInterface::class));

        $this->expectException(NotLoadableException::class);
        $this->expectExceptionMessage('Source image "missing.jpg" not found.');

        $loader->find('missing.jpg');
    }
}
