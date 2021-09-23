<?php


namespace Adeliom\EasyMediaBundle\Twig;

use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EasyMediaExtension extends AbstractExtension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('resolve_media', [$this, 'resolveMedia']),
            new TwigFilter('media_infos', [$this, 'mediaInfos']),
            new TwigFilter('media_meta', [$this, 'mediaMeta']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('mime_icon', [$this, 'getMimeIcon']),
            new TwigFunction('file_is_type', [$this, 'fileIsType']),
        ];
    }

    public function resolveMedia($file)
    {
        if(is_string($file) && !empty($file)){
            $rootPath = $this->container->getParameter("easy_media.storage");
            $basePath = $this->container->getParameter("easy_media.base_url");
            $path = $rootPath . DIRECTORY_SEPARATOR . str_replace($basePath, "", $file);
            if (file_exists($path) && is_file($path)){
                $file = new File($path);
            }
        }

        if($file instanceof File){
            $baseUrl = $this->container->getParameter("easy_media.base_url");
            $storage = $this->container->getParameter("easy_media.storage");

            $path = $file->getPathname();
            $path = str_replace($storage, "", $path);
            $str = "{$baseUrl}/{$path}";
            $str = preg_replace('/\/+/', '/', $str);
            $str = str_replace(':/', '://', $str);
            return $str;
        }
        return null;
    }

    public function mediaMeta($file, ?string $key = null){

        if(is_string($file) && !empty($file)){
            $rootPath = $this->container->getParameter("easy_media.storage");
            $basePath = $this->container->getParameter("easy_media.base_url");
            $path = $rootPath . DIRECTORY_SEPARATOR . str_replace($basePath, "", $file);
            if (file_exists($path) && is_file($path)){
                $file = new File($path);
            }
        }

        if($file instanceof File){
            if($path = $this->resolveMedia($file)) {
                $metasService = $this->container->get("easy_media.service.metas");
                $basePath = $this->container->getParameter("easy_media.base_url");
                $rootPath = $this->container->getParameter("easy_media.storage");
                $path = DIRECTORY_SEPARATOR . str_replace($basePath, "", $path);
                $meta = null;

                if($key){
                    if($meta = $metasService->getMeta($path, $key)){
                        return $meta->getMetaValue();
                    }
                }else{
                    $metas = [];
                    if($meta = $metasService->getMetas($path)){
                        foreach ($meta as $item){
                            $metas[$item->getMetaKey()] = $item->getMetaValue();
                        }
                        return $metas;
                    }
                }
            }
        }
        return null;
    }

    public function mediaInfos($file)
    {
        if(is_string($file) && !empty($file)){
            $rootPath = $this->container->getParameter("easy_media.storage");
            $basePath = $this->container->getParameter("easy_media.base_url");
            $path = $rootPath . DIRECTORY_SEPARATOR . str_replace($basePath, "", $file);
            if (file_exists($path) && is_file($path)){
                $file = new File($path);
            }
        }

        if($file instanceof File){
            if($path = $this->resolveMedia($file)){
                $basePath = $this->container->getParameter("easy_media.base_url");
                $rootPath = $this->container->getParameter("easy_media.storage");
                $path = str_replace($basePath, "", $path);

                $adapter = new LocalFilesystemAdapter($rootPath);
                $filesystem = new Filesystem($adapter);
                $metasService = $this->container->get("easy_media.service.metas");
                $LMF = $this->container->getParameter("easy_media.last_modified_format");

                $detector = new FinfoMimeTypeDetector();

                /** @var FileAttributes $file */
                $time = $filesystem->lastModified($path) ?? null;
                $mimeType = $detector->detectMimeTypeFromFile($rootPath . DIRECTORY_SEPARATOR . $path);
                $metas = $metasService->getMetas(DIRECTORY_SEPARATOR . $path);

                $title = current(array_filter($metas, function ($item){return $item->getMetaKey() == "title";}));
                $alt = current(array_filter($metas, function ($item){return $item->getMetaKey() == "alt";}));
                $description = current(array_filter($metas, function ($item){return $item->getMetaKey() == "description";}));
                $extra = current(array_filter($metas, function ($item){return !in_array($item->getMetaKey(), ["title","alt","description","dimensions"]); }));


                return [
                    'name'                   => basename($path),
                    'type'                   => $mimeType,
                    'size'                   => $filesystem->fileSize($path),
                    'visibility'             => $filesystem->visibility($path),
                    'path'                   => $path,
                    'storage_path'           => dirname($path),
                    'last_modified'          => $time,
                    'last_modified_formated' => $time ? (new \DateTime("@$time"))->format($LMF) : null,
                    'metas' => [
                        "title" => $title ? $title->getMetaValue() : null,
                        "alt" => $alt ? $alt->getMetaValue() : null,
                        "description" => $description ? $description->getMetaValue() : null,
                        "extra" => $extra,
                    ]

                ];
            }
        }

        return null;
    }

    public function fileIsType($file, $compare){
        if(is_string($file) && !empty($file)){
            $rootPath = $this->container->getParameter("easy_media.storage");
            $basePath = $this->container->getParameter("easy_media.base_url");
            $path = $rootPath . DIRECTORY_SEPARATOR . str_replace($basePath, "", $file);
            if (file_exists($path) && is_file($path)){
                $file = new File($path);
            }
        }

        if($file instanceof File){
            $mimes = $this->container->getParameter("easy_media.extended_mimes");
            $type = $file->getMimeType();
            if ($type) {
                if ($compare == 'image' && in_array($type, $mimes["image"])) {
                    return true;
                }

                // because "pdf" shows up as "application" type.includes('pdf')
                if (($type && strpos($type, "pdf") !== false) && $compare != 'pdf') {
                    return false;
                }

                // because "archive" shows up as "application"
                if (($type && strpos($type, 'compressed') !== false) || in_array($type, $mimes["archive"])) {
                    return $compare == 'compressed';
                }

                return $type && strpos($type, $compare) !== false;
            }
        }
        return false;
    }

    public function getMimeIcon($mime_type) {
        // List of official MIME Types: http://www.iana.org/assignments/media-types/media-types.xhtml
        $icon_classes = array(
            // Media
            'image' => 'fa-file-image-o',
            'audio' => 'fa-file-audio-o',
            'video' => 'fa-file-video-o',
            // Documents
            'application/pdf' => 'fa-file-pdf-o',
            'application/msword' => 'fa-file-word-o',
            'application/vnd.ms-word' => 'fa-file-word-o',
            'application/vnd.oasis.opendocument.text' => 'fa-file-word-o',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'fa-file-word-o',
            'application/vnd.ms-excel' => 'fa-file-excel-o',
            'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'fa-file-excel-o',
            'application/vnd.oasis.opendocument.spreadsheet' => 'fa-file-excel-o',
            'application/vnd.ms-powerpoint' => 'fa-file-powerpoint-o',
            'application/vnd.openxmlformats-officedocument.presentationml' => 'fa-file-powerpoint-o',
            'application/vnd.oasis.opendocument.presentation' => 'fa-file-powerpoint-o',
            'text/plain' => 'fa-file-text-o',
            'text/html' => 'fa-file-code-o',
            'application/json' => 'fa-file-code-o',
            // Archives
            'application/gzip' => 'fa-file-archive-o',
            'application/zip' => 'fa-file-archive-o',
        );
        foreach ($icon_classes as $text => $icon) {
            if (strpos($mime_type, $text) === 0) {
                return $icon;
            }
        }
        return 'fa-file-o';
    }
}
