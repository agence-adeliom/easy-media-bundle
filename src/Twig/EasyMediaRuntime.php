<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Twig;

use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Liip\ImagineBundle\Controller\ImagineController;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Service\FilterService;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EasyMediaRuntime implements RuntimeExtensionInterface
{
    protected EasyMediaManager $manager;
    protected Environment $twig;
    protected FilterManager $filterManager;
    protected CacheManager $cacheManager;

    public function __construct(EasyMediaManager $manager, Environment $twig, FilterManager $filterManager, CacheManager $cacheManager)
    {
        $this->manager = $manager;
        $this->twig = $twig;
        $this->filterManager = $filterManager;
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param int|string|Media $media
     * @return array|string|string[]|null
     */
    public function resolveMedia($media)
    {
        $media = $this->getMedia($media);
        if (null === $media) {
            return null;
        }

        return $this->buildPath($media);
    }

    /**
     * @param int|string|Media $media
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public function mediaMeta($media, ?string $key = null, $default = null)
    {
        $media = $this->getMedia($media);
        if (null === $media) {
            return null;
        }

        if ($key) {
            return $media->getMeta($key, $default);
        }
        return $media->getMetas();
    }

    /**
     * @param int|string|Media $media
     * @return array|null
     */
    public function mediaInfos($media)
    {
        $media = $this->getMedia($media);
        if ($media === null) {
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
            'path' => $this->buildPath($media),
            'storage_path' => $path,
            'last_modified' => $time,
            'last_modified_formated' => $time ? $this->manager->getHelper()->getItemTime($time) : null,
            'metas' => $metas,
        ];
    }

    /**
     * @param int|string|Media $media
     * @param string $compare
     * @return bool|null
     */
    public function fileIsType($media, string $compare)
    {
        $media = $this->getMedia($media);
        if (null === $media) {
            return null;
        }

        $type = $media->getMime();

        return $this->manager->getHelper()->fileIsType($type, $compare);
    }

    /**
     * @param string $mime_type
     * @return string
     */
    public function getMimeIcon(string $mime_type)
    {
        return EasyMediaHelper::mime2icon($mime_type);
    }

    /**
     * @param int|string|Media $media
     * @return null|Media
     */
    private function getMedia($media): ?Media
    {
        $class = $this->manager->getHelper()->getMediaClassName();
        if (!\is_int($media) && !\is_string($media) && !$media instanceof $class) {
            throw new \TypeError(sprintf(
                'Media parameter must be either an identifier or the media itself for Twig functions, "%s" given.',
                \is_object($media) ? 'instance of '.\get_class($media) : \gettype($media)
            ));
        }

        if (!$media instanceof $class) {
            $media = $this->manager->getMedia($media);
        }

        if (!$media instanceof $class) {
            return null;
        }

        return $media;
    }

    private function buildPath($media)
    {
        return $this->manager->getHelper()->resolveUrl($media);
    }

    /**
     * @param int|string|Media $media
     * @param array<string, mixed>      $options
     */
    public function media($media, string $format = "reference", array $options = []): string
    {
        $media = $this->getMedia($media);
        $template = null;
        if (null === $media) {
            return '';
        }

        if($this->fileIsType($media, "image")){
            $options = $this->getImageHelperProperties($media, $format, $options);
            $template = "@EasyMedia/render/image.html.twig";
        }

        if($this->fileIsType($media, "oembed")){
            $options = $this->getOembedHelperProperties($media, $format, $options);
            $template = "@EasyMedia/render/oembed.html.twig";
        }

        if($this->fileIsType($media, "video")){
            $options = $this->getVideoHelperProperties($media, $format, $options);
            $template = "@EasyMedia/render/video.html.twig";
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
     * @param int|string|Media $media
     * @param array|string $format
     */
    public function path($media, $format = "reference"): string
    {
        $media = $this->getMedia($media);
        if (null === $media) {
            return '';
        }

        if($format !== "reference" && $this->fileIsType($media, "image")){
            if(is_array($format)){
                return $this->cacheManager->getRuntimePath($media->getPath(), $format);
            }
            return $this->cacheManager->getBrowserPath($media->getPath(), $format);
        }

        if($this->fileIsType($media, "oembed")){
            return $media->getMeta("url");
        }

        return $this->buildPath($media);
    }

    private function getVideoHelperProperties(Media $media, string $format = "reference", array $options = []): array
    {
        $params = [
            'url' => $this->buildPath($media),
            'sources' => [
                [
                    "src" => $this->buildPath($media),
                    "type" => $media->getMime()
                ]
            ]
        ];

        return array_merge($params, $options);
    }

    private function getOembedHelperProperties(Media $media, string $format = "reference", array $options = []): array
    {
        $params = [
            'title' => $media->getMeta("title", $media->getName())
        ];
        $code = $media->getMeta("code");
        if ("reference" === $format) {
            $params += $code;
        }

        return array_merge($params, ["attributes" => $options]);
    }

    private function getImageHelperProperties(Media $media, string $format = "reference", array $options = []): array
    {
        if (isset($options['srcset'], $options['picture'])) {
            throw new \LogicException("The 'srcset' and 'picture' options must not be used simultaneously.");
        }

        $params = [
            'alt' => $media->getMeta("alt", $media->getName()),
            'title' => $media->getMeta("title", $media->getName())
        ];
        $box = $media->getMeta("dimensions");

        $params += [
            'ratio' => $box["ratio"] ?: null,
        ];

        if ("reference" === $format) {
            $params += [
                'src' => $this->path($media, $format),
                'width' => $box["width"] ?: null,
                'height' => $box["height"] ?: null,
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
                    if(\is_string($key) || isset($options['picture'])){
                        $src = $this->path($media, $formatName);
                        [$width, $height] = $settings[$formatName]["filters"]['thumbnail']["size"];
                        $mediaQuery = \is_string($key)
                            ? $key
                            : ($width ? sprintf('(max-width: %dpx)', $width) : null);

                        $pictureParams['source'][] = ['media' => $mediaQuery, 'srcset' => $src, 'width' => $width, 'ratio' => $width ? ($height / $width * 100) : null];
                    }else{
                        if (empty($settings)) {
                            throw new \RuntimeException(sprintf('The image format "%s" is not defined.
                                Is the format registered in your ``liip_imagine.filter_sets`` configuration?', $formatName));
                        }
                        $srcSetFormats += $settings;
                    }
                }
                unset($options['srcset'], $options['picture']);

                if(!empty($pictureParams)){
                    $params['src'] = $this->path($media, (isset($formats[$format]) ? $format : "reference"));
                    usort($pictureParams['source'], static function($a, $b) {
                        return ($a['width'] ?: 9999999) <=> ($b['width'] ?: 9999999);
                    });
                    if(isset($params["ratio"])){
                        $params["orientation"] = ($params["ratio"] && $params["ratio"] <= 100) ? "landscape" : "portrait";
                    }
                    $pictureParams['source'] = array_map(static function($source){
                        unset($source['width'], $source['ratio']);
                        return $source;
                    },$pictureParams['source']);
                    $pictureParams['img'] = $params + $options;
                    $params = ['picture' => $pictureParams];
                }else{
                    foreach ($srcSetFormats as $formatName => $settings) {
                        [$width, $height] = $settings["filters"]['thumbnail']["size"];
                        $srcSet[] = [
                            "width" => $width ?: null,
                            "set" => sprintf('%s %dw', $this->path($media, $formatName), $width),
                        ];
                    }

                    // The reference format is not in the formats list
                    $srcSet[] = [
                        "width" => $box["width"] ?: null,
                        "set" => sprintf(
                            '%s %dw',
                            $this->path($media),
                            $box["width"] ?: null
                        )
                    ];

                    usort($srcSet, static function($a, $b) {
                        return ($a['width'] ?: 9999999) <=> ($b['width'] ?: 9999999);
                    });

                    $srcSet = array_map(static function($source){
                        return $source['set'];
                    },$srcSet);

                    $params['srcset'] = implode(', ', $srcSet);
                    $params['src'] = $this->path($media);
                    $params['sizes'] = sprintf('(max-width: %1$dpx) 100vw, %1$dpx', $box["width"] ?: null);
                }
            }
        }elseif(count($formats) > 1){
            $pictureParams = [];
            foreach ($formats as $formatName => $settings) {
                $src = $this->path($media, $formatName);
                [$width, $height] = $settings["filters"]['thumbnail']["size"];
                $mediaQuery = $width ? sprintf('(max-width: %dpx)', $width) : null;
                $pictureParams['source'][] = ['media' => $mediaQuery, 'srcset' => $src, 'width' => $width, 'ratio' => $width ? ($height / $width * 100) : null ];
            }
            usort($pictureParams['source'], static function($a, $b) {
                return ($a['width'] ?: 9999999) <=> ($b['width'] ?: 9999999);
            });
            $pictureParams['source'] = array_map(static function($source){
                unset($source['width'], $source['ratio']);
                return $source;
            },$pictureParams['source']);
            $pictureParams['img'] = $params + $options;
            $params = ['picture' => $pictureParams];
        }elseif(isset($formats[$format])){
            $params += [
                'src' => $this->path($media, $format),
                'width' => $box["width"] ?: null,
                'height' => $box["height"] ?: null,
            ];
        }
        if(isset($params["ratio"])){
            $params["orientation"] = ($params["ratio"] && $params["ratio"] <= 100) ? "landscape" : "portrait";
        }

        return array_merge($params, $options);
    }

    private function getFormat(string $format){
        return array_filter($this->filterManager->getFilterConfiguration()->all(), static function ($config, $key) use ($format){
            return ($key === "default" || str_starts_with($key, $format));
        }, ARRAY_FILTER_USE_BOTH);
    }
}
