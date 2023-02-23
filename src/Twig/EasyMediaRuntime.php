<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Twig;

use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Doctrine\Persistence\Proxy;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class EasyMediaRuntime implements RuntimeExtensionInterface
{
    public function __construct(protected EasyMediaManager $manager, protected Environment $twig, protected FilterManager $filterManager, protected CacheManager $cacheManager)
    {
    }

    /**
     * @return array|string|string[]|null
     */
    public function resolveMedia(int|string|Media $media)
    {
        $media = $this->getMedia($media);
        if (!$media instanceof \Adeliom\EasyMediaBundle\Entity\Media) {
            return null;
        }

        return $this->manager->getPath($media);
    }

    /**
     * @param mixed|null $default
     *
     * @return array|mixed|null
     */
    public function mediaMeta(int|string|Media $media, ?string $key = null, $default = null)
    {
        $media = $this->getMedia($media);
        if (!$media instanceof \Adeliom\EasyMediaBundle\Entity\Media) {
            return null;
        }

        if ($key) {
            return $media->getMeta($key, $default);
        }

        return $media->getMetas();
    }

    /**
     * @return array|null
     */
    public function mediaInfos(int|string|Media $media)
    {
        $media = $this->getMedia($media);
        if (!$media instanceof \Adeliom\EasyMediaBundle\Entity\Media) {
            return null;
        }

        $path = $media->getPath();
        $time = $media->getLastModified();
        $metas = $media->getMetas();

        return [
            'id' => $media->getId(),
            'name' => $media->getName(),
            'type' => $media->getMime(),
            'size' => $media->getSize(),
            'path' => $this->path($media),
            'download_url' => $this->downloadUrl($media),
            'storage_path' => $path,
            'last_modified' => $time,
            'last_modified_formated' => $time ? $this->manager->getHelper()->getItemTime($time) : null,
            'metas' => $metas,
        ];
    }

    /**
     * @return bool|null
     */
    public function fileIsType(int|string|Media $media, string $compare)
    {
        $media = $this->getMedia($media);
        if (!$media instanceof \Adeliom\EasyMediaBundle\Entity\Media) {
            return null;
        }

        $type = $media->getMime();

        return $this->manager->getHelper()->fileIsType($type, $compare);
    }

    /**
     * @return string
     */
    public function getMimeIcon(string $mime_type)
    {
        return EasyMediaHelper::mime2icon($mime_type);
    }

    private function getMedia(int|string|Media $media): ?Media
    {
        $class = $this->manager->getHelper()->getMediaClassName();
        if (!\is_int($media) && !\is_string($media) && !$media instanceof $class) {
            throw new \TypeError(sprintf('Media parameter must be either an identifier or the media itself for Twig functions, "%s" given.', \is_object($media) ? 'instance of '.$media::class : \gettype($media)));
        }

        try {
            if($media instanceof Proxy){
                $media->__load();
            }
            if (!($media instanceof $class)) {
                $media = $this->manager->getMedia($media);
            }
            if (!$media instanceof $class) {
                return null;
            }
            return $media;
        }catch (\Exception){
            return null;
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function media(int|string|Media $media, string $format = 'reference', array $options = []): string
    {
        $media = $this->getMedia($media);
        $template = null;
        if (!$media instanceof \Adeliom\EasyMediaBundle\Entity\Media) {
            return '';
        }

        if ($this->fileIsType($media, 'image')) {
            $options = $this->getImageHelperProperties($media, $format, $options);
            $template = '@EasyMedia/render/image.html.twig';
        }

        if ($this->fileIsType($media, 'oembed')) {
            $options = $this->getOembedHelperProperties($media, $format, $options);
            $template = '@EasyMedia/render/oembed.html.twig';
        }

        if ($this->fileIsType($media, 'video')) {
            $options = $this->getVideoHelperProperties($media, $format, $options);
            $template = '@EasyMedia/render/video.html.twig';
        }

        if (null === $template) {
            return '';
        }

        return $this->twig->render($template, [
            'media' => $media,
            'format' => $format,
            'options' => $options,
        ]);
    }

    /**
     * @param mixed[]|string $format
     */
    public function path(int|string|Media $media, array|string $format = 'reference'): ?string
    {
        $media = $this->getMedia($media);
        if (!$media instanceof \Adeliom\EasyMediaBundle\Entity\Media) {
            return '';
        }

        if ('reference' !== $format && $this->fileIsType($media, 'image')) {
            if (is_array($format)) {
                return $this->cacheManager->getRuntimePath($media->getPath(), $format);
            }

            return $this->cacheManager->getBrowserPath($media->getPath(), $format);
        }

        if ($this->fileIsType($media, 'oembed')) {
            return $media->getMeta('url');
        }

        return $this->manager->publicUrl($media);
    }

    public function downloadUrl(int|string|Media $media): string
    {
        $media = $this->getMedia($media);
        if (!$media instanceof \Adeliom\EasyMediaBundle\Entity\Media) {
            return '';
        }

        return $this->manager->downloadUrl($media);
    }

    private function getVideoHelperProperties(Media $media, string $format = 'reference', array $options = []): array
    {
        $params = [
            'url' => $this->path($media, $format),
            'sources' => [
                [
                    'src' => $this->path($media, $format),
                    'type' => $media->getMime(),
                ],
            ],
        ];

        return array_merge($params, $options);
    }

    private function getOembedHelperProperties(Media $media, string $format = 'reference', array $options = []): array
    {
        $params = [
            'title' => $media->getMeta('title', $media->getName()),
        ];
        $code = $media->getMeta('code');
        if ('reference' === $format) {
            $params += $code;
        }

        return array_merge($params, ['attributes' => $options]);
    }

    private function getImageHelperProperties(Media $media, string $format = 'reference', array $options = []): array
    {
        if (isset($options['srcset'], $options['picture'])) {
            throw new \LogicException("The 'srcset' and 'picture' options must not be used simultaneously.");
        }

        $params = [
            'alt' => $media->getMeta('alt', $media->getName()),
            'title' => $media->getMeta('title', $media->getName()),
        ];
        $box = $media->getMeta('dimensions');

        $params += [
            'ratio' => $box['ratio'] ?: null,
        ];

        if ('reference' === $format) {
            $params += [
                'src' => $this->path($media, $format),
                'width' => $box['width'] ?: null,
                'height' => $box['height'] ?: null,
            ];

            return array_merge($params, $options);
        }

        $formats = $this->getFormat($format);

        if (isset($options['srcset']) || isset($options['picture'])) {
            $set = $options['srcset'] ?? $options['picture'];
            if (\is_array($set)) {
                $srcSetFormats = [];
                $pictureParams = [];
                foreach ($set as $key => $formatName) {
                    $settings = $this->getFormat($formatName);
                    if (\is_string($key) || isset($options['picture'])) {
                        $src = $this->path($media, $formatName);
                        [$width, $height] = $settings[$formatName]['filters']['thumbnail']['size'];
                        $mediaQuery = \is_string($key)
                            ? $key
                            : ($width ? sprintf('(max-width: %dpx)', $width) : null);

                        $pictureParams['source'][] = ['media' => $mediaQuery, 'srcset' => $src, 'width' => $width, 'ratio' => $width ? ($height / $width * 100) : null];
                    } else {
                        if (empty($settings)) {
                            throw new \RuntimeException(sprintf('The image format "%s" is not defined.
                                Is the format registered in your ``liip_imagine.filter_sets`` configuration?', $formatName));
                        }

                        $srcSetFormats += $settings;
                    }
                }

                unset($options['srcset'], $options['picture']);

                if (!empty($pictureParams)) {
                    $params['src'] = $this->path($media, isset($formats[$format]) ? $format : 'reference');
                    usort($pictureParams['source'], static fn ($a, $b) => ($a['width'] ?: 9_999_999) <=> ($b['width'] ?: 9_999_999));
                    if (isset($params['ratio'])) {
                        $params['orientation'] = ($params['ratio'] && $params['ratio'] <= 100) ? 'landscape' : 'portrait';
                    }

                    $pictureParams['source'] = array_map(static function ($source) {
                        unset($source['width'], $source['ratio']);

                        return $source;
                    }, $pictureParams['source']);
                    $pictureParams['img'] = $params + $options;
                    $params = ['picture' => $pictureParams];
                } else {
                    foreach ($srcSetFormats as $formatName => $settings) {
                        [$width, $height] = $settings['filters']['thumbnail']['size'];
                        $srcSet[] = [
                            'width' => $width ?: null,
                            'set' => sprintf('%s %dw', $this->path($media, $formatName), $width),
                        ];
                    }

                    // The reference format is not in the formats list
                    $srcSet[] = [
                        'width' => $box['width'] ?: null,
                        'set' => sprintf(
                            '%s %dw',
                            $this->path($media),
                            $box['width'] ?: null
                        ),
                    ];

                    usort($srcSet, static fn ($a, $b) => ($a['width'] ?: 9_999_999) <=> ($b['width'] ?: 9_999_999));

                    $srcSet = array_map(static fn ($source) => $source['set'], $srcSet);

                    $params['srcset'] = implode(', ', $srcSet);
                    $params['src'] = $this->path($media);
                    $params['sizes'] = sprintf('(max-width: %1$dpx) 100vw, %1$dpx', $box['width'] ?: null);
                }
            }
        } elseif ((is_countable($formats) ? count($formats) : 0) > 1) {
            $pictureParams = [];
            foreach ($formats as $formatName => $settings) {
                $src = $this->path($media, $formatName);
                [$width, $height] = $settings['filters']['thumbnail']['size'];
                $mediaQuery = $width ? sprintf('(max-width: %dpx)', $width) : null;
                $pictureParams['source'][] = ['media' => $mediaQuery, 'srcset' => $src, 'width' => $width, 'ratio' => $width ? ($height / $width * 100) : null];
            }

            usort($pictureParams['source'], static fn ($a, $b) => ($a['width'] ?: 9_999_999) <=> ($b['width'] ?: 9_999_999));
            $pictureParams['source'] = array_map(static function ($source) {
                unset($source['width'], $source['ratio']);

                return $source;
            }, $pictureParams['source']);
            $pictureParams['img'] = $params + $options;
            $params = ['picture' => $pictureParams];
        } elseif (isset($formats[$format])) {
            $params += [
                'src' => $this->path($media, $format),
                'width' => $box['width'] ?: null,
                'height' => $box['height'] ?: null,
            ];
        }

        if (isset($params['ratio'])) {
            $params['orientation'] = ($params['ratio'] && $params['ratio'] <= 100) ? 'landscape' : 'portrait';
        }

        return array_merge($params, $options);
    }

    private function getFormat(string $format)
    {
        return array_filter($this->filterManager->getFilterConfiguration()->all(), static fn ($config, $key) => 'default' === $key || str_starts_with((string) $key, $format), ARRAY_FILTER_USE_BOTH);
    }
}
