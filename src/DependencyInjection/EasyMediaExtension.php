<?php

namespace Adeliom\EasyMediaBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class EasyMediaExtension extends Extension implements PrependExtensionInterface
{

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        foreach ($config as $k=>$v){
            $container->setParameter('easy_media.'.$k, $v);
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        // TODO: Set custom doctrine config
        // $doctrineConfig = [];
        // $doctrineConfig['orm']['resolve_target_entities']['Acme\FooBundle\Entity\UserInterface'] = $config['user_provider'];
        // $doctrineConfig['orm']['mappings'][] = array(
        //     'name' => 'AcmeFooBundle',
        //     'is_bundle' => true,
        //     'type' => 'xml',
        //     'prefix' => 'Acme\FooBundle\Entity'
        // );
        $container->prependExtensionConfig('easy_media', $config);
        // TODO: Set custom twig config
        $twigConfig = [];
        //$twigConfig['globals']['easy_media_service'] = "@easy_media.service";
        $twigConfig['paths'][__DIR__.'/../Resources/views'] = "easy_media";
        $twigConfig['globals']['easy_media'] = [];
        foreach ($config as $k=>$v){
            $twigConfig['globals']['easy_media'][$k] = $v;
        }
        // $twigConfig['paths'][__DIR__.'/../Resources/public'] = "easy_media.public";
        $container->prependExtensionConfig('twig', $twigConfig);
    }

    public function getAlias(): string
    {
        return 'easy_media';
    }
}
