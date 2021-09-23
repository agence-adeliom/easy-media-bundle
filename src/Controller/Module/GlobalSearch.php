<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Doctrine\Common\Collections\ArrayCollection;
use League\Flysystem\FileAttributes;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Symfony\Component\HttpFoundation\JsonResponse;

trait GlobalSearch
{
    public function globalSearch()
    {
        $detector = new FinfoMimeTypeDetector();

        $results = (new ArrayCollection($this->getFolderContent('/', true)))
            ->filter(function ($item) { // remove unwanted & dirs
                return !(preg_grep($this->ignoreFiles, [$item->path()]) || $item->isDir());
            })->map(function ($file) use ($detector) {
                /** @var FileAttributes $file */
                $path = $file->path();
                $time = $file->lastModified() ?? null;
                $mimeType = $detector->detectMimeTypeFromFile($this->rootPath . DIRECTORY_SEPARATOR . $path);

                return [
                    'name'                   => basename($file->path()),
                    'type'                   => $mimeType,
                    'path'                   => $this->resolveUrl($path),
                    'dir_path'               => dirname($file->path()) ?: '/',
                    'storage_path'           => $path,
                    'size'                   => $file->fileSize(),
                    'last_modified'          => $time,
                    'last_modified_formated' => $this->getItemTime($time),
                ];

            })->toArray();


        return new JsonResponse($results);
    }
}
