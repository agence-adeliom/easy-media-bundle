<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Doctrine\Common\Collections\ArrayCollection;
use League\Flysystem\FileAttributes;
use Symfony\Component\HttpFoundation\JsonResponse;

trait GlobalSearch
{
    public function globalSearch()
    {
        $results = (new ArrayCollection($this->getFolderContent('/', true)))
            ->filter(fn($item) => ! preg_grep($this->ignoreFiles, [$item->path()]) && ! $item->isDir())->map(function ($file) {
                /** @var FileAttributes $file */
                $path = $file->path();
                $time = $file->lastModified();
                $mimeType = $file->mimeType();

                return [
                    'name' => basename($file->path()),
                    'type' => $mimeType,
                    'path' => $this->helper->resolveUrl($path),
                    'dir_path' => dirname($file->path()) ?: '/',
                    'storage_path' => $path,
                    'size' => $file->fileSize(),
                    'last_modified' => $time,
                    'last_modified_formated' => $this->helper->getItemTime($time),
                ];
            })->toArray();

        return new JsonResponse($results);
    }
}
