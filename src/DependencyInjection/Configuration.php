<?php

namespace Adeliom\EasyMediaBundle\DependencyInjection;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('easy_media');

        $rootNode = $builder->getRootNode();
        $rootNode->children()
            ->scalarNode('storage')
                ->defaultValue('%kernel.project_dir%/public/upload')
            ->end()
            ->scalarNode('base_url')
                ->defaultValue('/upload/')
            ->end()
            ->scalarNode('media_entity')
                ->isRequired()
                ->validate()
                ->ifString()
                ->then(function($value) {
                    if (!class_exists($value) || !is_a($value, Media::class, true)) {
                        throw new InvalidConfigurationException(sprintf(
                            'Media class must be a valid class extending %s. "%s" given.',
                            Media::class, $value
                        ));
                    }
                    return $value;
                })
                ->end()
            ->end()
            ->scalarNode('folder_entity')
                ->isRequired()
                ->validate()
                ->ifString()
                ->then(function($value) {
                    if (!class_exists($value) || !is_a($value, Folder::class, true)) {
                        throw new InvalidConfigurationException(sprintf(
                            'Media Folder class must be a valid class extending %s. "%s" given.',
                            Folder::class, $value
                        ));
                    }
                    return $value;
                })
                ->end()
            ->end()
            ->scalarNode('ignore_files')
                ->defaultValue('/^\..*/')
            ->end()
            ->scalarNode('allowed_fileNames_chars')
                ->defaultValue("\._\-\'\s\(\),")
            ->end()
            ->scalarNode('allowed_folderNames_chars')
                ->defaultValue("_\-\s")
            ->end()
            ->arrayNode('unallowed_mimes')
                ->scalarPrototype()->end()
                ->defaultValue([
                    'php',
                    'java',
                ])
            ->end()
            ->arrayNode('unallowed_ext')
                ->defaultValue([
                    'php',
                    'jav',
                    'py',
                ])
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('extended_mimes')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('image')->scalarPrototype()->end()->isRequired()->defaultValue(['binary/octet-stream'])->end()
                    ->arrayNode('archive')->scalarPrototype()->end()->isRequired()->defaultValue(['application/x-tar', 'application/zip'])->end()
                ->end()
            ->end()
            ->scalarNode('sanitized_text')
                ->defaultValue('uniqid')
            ->end()
            ->scalarNode('last_modified_format')
                ->defaultValue('Y-m-d')
            ->end()
            ->booleanNode('hide_files_ext')
                ->defaultTrue()
            ->end()
            ->booleanNode('get_folder_info')
                ->defaultTrue()
            ->end()
            ->booleanNode('enable_broadcasting')
                ->defaultFalse()
            ->end()
            ->integerNode('pagination_amount')
                ->defaultValue(50)
                ->min(4)
            ->end()
        ->end();

        return $builder;
    }
}
