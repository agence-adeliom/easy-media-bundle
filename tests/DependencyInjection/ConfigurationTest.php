<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests\DependencyInjection;

use Adeliom\EasyMediaBundle\DependencyInjection\Configuration;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestFolder;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

#[CoversClass(\Adeliom\EasyMediaBundle\DependencyInjection\Configuration::class)]
final class ConfigurationTest extends TestCase
{
    public function testConfigurationAcceptsEntitiesAndAppliesDefaults(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'media_entity' => TestMedia::class,
            'folder_entity' => TestFolder::class,
        ]]);

        self::assertSame('default.storage', $config['storage_name']);
        self::assertSame('/', $config['base_url']);
        self::assertSame(TestMedia::class, $config['media_entity']);
        self::assertSame(TestFolder::class, $config['folder_entity']);
        self::assertSame(['php', 'java'], $config['unallowed_mimes']);
        self::assertSame(50, $config['pagination_amount']);
    }

    public function testConfigurationRejectsInvalidMediaEntity(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(), [[
            'media_entity' => \stdClass::class,
            'folder_entity' => TestFolder::class,
        ]]);
    }
}
