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
    protected $rootPath;
    protected $basePath;
    protected $class;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->rootPath = $this->container->getParameter("easy_media.storage");
        $this->basePath = $this->container->getParameter("easy_media.base_url");
        $this->class = $this->container->getParameter("easy_media.media_entity");
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

    private function getMedia($file){
        $class = $this->class;
        if(!($file instanceof $class)){
            $file = $this->container->get("doctrine.orm.entity_manager")->getRepository($class)->find($file);
        }

        if(null === $file){
            return null;
        }

        return $file;
    }

    public function resolveMedia($file)
    {
        $file = $this->getMedia($file);
        if(null === $file){
            return null;
        }
        return $this->buildPath($file);
    }

    private function buildPath($media){
        $path = $this->basePath . '/' . $media->getPath();
        $path = preg_replace('/\/+/', '/', $path);
        return str_replace(':/', '://', $path);
    }

    public function mediaMeta($file, ?string $key = null){

        $file = $this->getMedia($file);
        if(null === $file){
            return null;
        }
        $metas = $file->getMetas();
        if($key){
            return $metas[$key] ?? null;
        }
        return $metas;
    }

    public function mediaInfos($file)
    {
        $file = $this->getMedia($file);
        if(null === $file){
            return null;
        }

        $LMF = $this->container->getParameter("easy_media.last_modified_format");

        $path = $file->getPath();
        $time = $file->getLastModified() ?? null;
        $metas = $file->getMetas();

        return [
            'id'                     => $file->getId(),
            'name'                   => $file->getName(),
            'type'                   => $file->getMime(),
            'size'                   => $file->getSize(),
            'path'                   => $this->buildPath($file),
            'storage_path'           => $path,
            'last_modified'          => $time,
            'last_modified_formated' => $time ? (new \DateTime("@$time"))->format($LMF) : null,
            'metas' => $metas
        ];

    }

    public function fileIsType($file, $compare){

        $file = $this->getMedia($file);
        if(null === $file){
            return null;
        }

        $mimes = $this->container->getParameter("easy_media.extended_mimes");
        $type = $file->getMime();
        if ($type) {
            if ($compare == 'image' && in_array($type, $mimes["image"])) {
                return true;
            }

            // because "oembed" shows up as "application" type.includes('oembed')
            if (($type && strpos($type, "oembed") !== false) && $compare != 'oembed') {
                return false;
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
