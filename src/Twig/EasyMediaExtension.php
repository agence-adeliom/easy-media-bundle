<?php


namespace Adeliom\EasyMediaBundle\Twig;

use Adeliom\EasyMediaBundle\Service\EasyMediaHelper;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EasyMediaExtension extends AbstractExtension
{
    public function __construct(protected EasyMediaManager $manager)
    {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('resolve_media', [$this, 'resolveMedia']),
            new TwigFilter('media_infos', [$this, 'mediaInfos']),
            new TwigFilter('media_meta', [$this, 'mediaMeta']),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
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

    private function buildPath($media): array|string{
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

    public function mediaInfos($file): ?array
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

    public function fileIsType($file, $compare): ?bool
    {

        $file = $this->getMedia($file);
        if(null === $file){
            return null;
        }

        $type = $file->getMime();
        return $this->manager->getHelper()->fileIsType($type, $compare);
    }

    public function getMimeIcon($mime_type): string
    {
        return EasyMediaHelper::mime2icon($mime_type);
    }
}
