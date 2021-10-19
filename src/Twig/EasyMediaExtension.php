<?php


namespace Adeliom\EasyMediaBundle\Twig;

use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
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
     * @var EasyMediaManager
     */
    protected $manager;

    public function __construct(EasyMediaManager $manager)
    {
        $this->manager = $manager;
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
        $class = $this->manager->getHelper()->getMediaClassName();
        if(!($file instanceof $class)){
            $file = $this->manager->getMedia($file);
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
        return $this->manager->getHelper()->resolveUrl($media->getPath());
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
            'last_modified_formated' => $time ? $this->manager->getHelper()->getItemTime($time) : null,
            'metas' => $metas
        ];

    }

    public function fileIsType($file, $compare){

        $file = $this->getMedia($file);
        if(null === $file){
            return null;
        }

        $type = $file->getMime();
        return $this->manager->getHelper()->fileIsType($type, $compare);
    }

    public function getMimeIcon($mime_type) {
        return EasyMediaHelper::mime2icon($mime_type);
    }
}
