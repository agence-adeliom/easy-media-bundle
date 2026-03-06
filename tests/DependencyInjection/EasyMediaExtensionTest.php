<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\DependencyInjection;

use Adeliom\EasyMediaBundle\DependencyInjection\EasyMediaExtension;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestFolder;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(\Adeliom\EasyMediaBundle\DependencyInjection\EasyMediaExtension::class)]
final class EasyMediaExtensionTest extends TestCase
{
    public function testExtensionLoadsParametersServicesAndStorageAlias(): void
    {
        $container = new ContainerBuilder();
        $extension = new EasyMediaExtension();

        $extension->load([[
            'storage_name' => 'custom.storage',
            'base_url' => '/media',
            'media_entity' => TestMedia::class,
            'folder_entity' => TestFolder::class,
        ]], $container);

        self::assertSame('easy_media', $extension->getAlias());
        self::assertSame('custom.storage', $container->getParameter('easy_media.storage_name'));
        self::assertSame('/media', $container->getParameter('easy_media.base_url'));
        self::assertSame(TestMedia::class, $container->getParameter('easy_media.media_entity'));
        self::assertSame(TestFolder::class, $container->getParameter('easy_media.folder_entity'));
        self::assertTrue($container->hasDefinition('easy_media.manager'));
        self::assertTrue($container->hasDefinition('easy_media.form.media'));
        self::assertTrue($container->hasDefinition('easy_media.twig.easy_media_extension'));
        self::assertSame('custom.storage', (string) $container->getAlias('easy_media.storage'));
    }

    public function testPrependRegistersTwigGlobalsAndViewPath(): void
    {
        $container = new ContainerBuilder();
        $extension = new EasyMediaExtension();

        $container->prependExtensionConfig('easy_media', [
            'storage_name' => 'default.storage',
            'base_url' => '/uploads',
            'media_entity' => TestMedia::class,
            'folder_entity' => TestFolder::class,
        ]);

        $extension->prepend($container);

        $twigConfig = $container->getExtensionConfig('twig')[0];

        self::assertSame('easy_media', array_values($twigConfig['paths'])[0]);
        self::assertSame('/uploads', $twigConfig['globals']['easy_media']['base_url']);
        self::assertSame(TestMedia::class, $twigConfig['globals']['easy_media']['media_entity']);
    }
}
