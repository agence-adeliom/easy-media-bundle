<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EasyMediaExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        foreach ($config as $k => $v) {
            $container->setParameter('easy_media.' . $k, $v);
        }

        $container->setAlias("easy_media.storage", $container->getParameter("easy_media.storage_name"));
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->prependExtensionConfig('easy_media', $config);
        $twigConfig = [];
        $twigConfig['paths'][__DIR__ . '/../Resources/views'] = 'easy_media';
        $twigConfig['globals']['easy_media'] = [];
        foreach ($config as $k => $v) {
            $twigConfig['globals']['easy_media'][$k] = $v;
        }

        $container->prependExtensionConfig('twig', $twigConfig);
    }

    public function getAlias(): string
    {
        return 'easy_media';
    }
}
