<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\Twig;

use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use Adeliom\EasyMediaBundle\Twig\EasyMediaRuntime;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

#[CoversClass(\Adeliom\EasyMediaBundle\Twig\EasyMediaRuntime::class)]
final class EasyMediaRuntimeTest extends TestCase
{
    public function testResolveMediaAndInfosReturnNullForUnknownMedia(): void
    {
        $runtime = $this->createRuntime(null);

        self::assertNull($runtime->resolveMedia(12));
        self::assertNull($runtime->mediaInfos(12));
        self::assertNull($runtime->mediaMeta(12, 'alt'));
        self::assertNull($runtime->fileIsType(12, 'image'));
        self::assertSame('', $runtime->path(12));
        self::assertSame('', $runtime->downloadUrl(12));
        self::assertSame('', $runtime->media(12));
    }

    public function testMediaInfosAndPathUseManagerAndHelperServices(): void
    {
        $media = $this->createMedia('image/jpeg', [
            'alt' => 'Hero alt',
            'title' => 'Hero title',
            'dimensions' => ['width' => 1200, 'height' => 800, 'ratio' => 66.67],
        ]);

        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager->expects(self::once())
            ->method('getRuntimePath')
            ->with('hero-jpg', ['filter' => 'thumb'])
            ->willReturn('/runtime/hero-jpg');

        $runtime = $this->createRuntime($media, null, null, $cacheManager);
        $infos = $runtime->mediaInfos($media);

        self::assertSame(7, $infos['id']);
        self::assertSame('Hero.jpg', $infos['name']);
        self::assertSame('image/jpeg', $infos['type']);
        self::assertSame(512, $infos['size']);
        self::assertSame('/public/hero-jpg', $infos['path']);
        self::assertSame('/download/hero-jpg', $infos['download_url']);
        self::assertSame('hero-jpg', $infos['storage_path']);
        self::assertSame(1700000000, $infos['last_modified']);
        self::assertSame('formatted-time', $infos['last_modified_formated']);
        self::assertSame('Hero alt', $runtime->mediaMeta($media, 'alt'));
        self::assertSame('/runtime/hero-jpg', $runtime->path($media, ['filter' => 'thumb']));
        self::assertTrue($runtime->fileIsType($media, 'image'));
    }

    public function testPathReturnsOembedUrlForOembedMedia(): void
    {
        $media = $this->createMedia('oembed', ['url' => 'https://video.example.test/watch/hero']);
        $runtime = $this->createRuntime($media);

        self::assertSame('https://video.example.test/watch/hero', $runtime->path($media));
    }

    public function testMediaRendersImageTemplateWithComputedReferenceOptions(): void
    {
        $media = $this->createMedia('image/jpeg', [
            'alt' => 'Hero alt',
            'title' => 'Hero title',
            'dimensions' => ['width' => 1200, 'height' => 800, 'ratio' => 66.67],
        ]);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@EasyMedia/render/image.html.twig',
                self::callback(static function (array $context) use ($media): bool {
                    return $context['media'] === $media
                        && 'reference' === $context['format']
                        && [
                            'alt' => 'Hero alt',
                            'title' => 'Hero title',
                            'ratio' => 66.67,
                            'src' => '/public/hero-jpg',
                            'width' => 1200,
                            'height' => 800,
                            'class' => 'hero-image',
                        ] === $context['options'];
                })
            )
            ->willReturn('<img src="/public/hero-jpg">');

        $runtime = $this->createRuntime($media, $twig);

        self::assertSame('<img src="/public/hero-jpg">', $runtime->media($media, 'reference', ['class' => 'hero-image']));
    }

    public function testMediaRejectsIncompatibleImageOptions(): void
    {
        $media = $this->createMedia('image/jpeg', [
            'dimensions' => ['width' => 1200, 'height' => 800, 'ratio' => 66.67],
        ]);
        $runtime = $this->createRuntime($media);

        $this->expectException(\LogicException::class);

        $runtime->media($media, 'thumb', ['srcset' => ['sm' => 'thumb_sm'], 'picture' => ['sm' => 'thumb_sm']]);
    }

    private function createRuntime(?TestMedia $media, ?Environment $twig = null, ?FilterManager $filterManager = null, ?CacheManager $cacheManager = null): EasyMediaRuntime
    {
        $helper = $this->createMock(EasyMediaHelper::class);
        $helper->method('getItemTime')->willReturn('formatted-time');
        $helper->method('fileIsType')->willReturnCallback(static function (string $type, string $compare): bool {
            return match ($compare) {
                'image' => str_starts_with($type, 'image/'),
                'video' => str_starts_with($type, 'video/'),
                'oembed' => 'oembed' === $type,
                default => false,
            };
        });

        $manager = $this->createMock(EasyMediaManager::class);
        $manager->method('getMedia')->willReturn($media);
        $manager->method('getHelper')->willReturn($helper);
        $manager->method('getPath')->willReturn($media?->getPath());
        $manager->method('publicUrl')->willReturn($media ? '/public/'.$media->getPath() : '');
        $manager->method('downloadUrl')->willReturn($media ? '/download/'.$media->getPath() : '');

        return new EasyMediaRuntime(
            $manager,
            $twig ?? $this->createMock(Environment::class),
            $filterManager ?? $this->createMock(FilterManager::class),
            $cacheManager ?? $this->createMock(CacheManager::class)
        );
    }

    private function createMedia(string $mime, array $metas): TestMedia
    {
        $media = new TestMedia();
        $property = new \ReflectionProperty($media, 'id');
        $property->setValue($media, 7);

        $media->setName('Hero.jpg');
        $media->setMime($mime);
        $media->setSize(512);
        $media->setLastModified(1700000000);
        $media->setMetas($metas);

        return $media;
    }
}
