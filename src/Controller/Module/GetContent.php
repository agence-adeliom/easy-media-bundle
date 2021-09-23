<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Doctrine\Common\Collections\ArrayCollection;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait GetContent
{

    /**
     * get files in path.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function getFiles(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $path = $data["path"] == '/' ? '' : $data["path"];
        /*if ($path && !$this->filesystem->fileExists($path)) {
            return new JsonResponse([
                'error' => $this->translator->trans('MediaManager::messages.error.doesnt_exist', ['attr' => $path]),
            ]);
        }*/

        return new JsonResponse(array_merge(
            $this->lockList(),
            [
                'files' => [
                    'path'  => $path,
                    'items' => $this->paginate($this->getData($path), $this->paginationAmount),
                ],
            ]
        ));
    }

    /**
     * get files list.
     *
     * @param mixed $dir
     */
    protected function getData($dir)
    {
        $list           = [];
        $dirList        = $this->getFolderContent($dir);
        $storageFolders = array_filter($this->getFolderListByType($dirList, 'dir'), [$this, 'ignoreFiles']);
        $storageFiles   = array_filter($this->getFolderListByType($dirList, 'file'), [$this, 'ignoreFiles']);
        // folders
        foreach ($storageFolders as $folder) {
            /** @var DirectoryAttributes $folder */

            $path = $folder->path();
            $time = $folder->lastModified() ?? null;
            $info = $this->GFI ? $this->getFolderInfoFromList($this->getFolderContent($path, true)) : [];

            $list[] = [
                'name'                   => basename($folder->path()),
                'type'                   => 'folder',
                'size'                   => $info['size'] ?? 0,
                'count'                  => $info['count'] ?? 0,
                'path'                   => $this->resolveUrl($path),
                'storage_path'           => $path,
                'last_modified'          => $time,
                'last_modified_formated' => $this->getItemTime($time),
            ];
        }

        $detector = new FinfoMimeTypeDetector();

        // files
        foreach ($storageFiles as $file) {
            /** @var FileAttributes $file */
            $path = $file->path();
            $time = $file->lastModified() ?? null;
            $mimeType = $detector->detectMimeTypeFromFile($this->rootPath . DIRECTORY_SEPARATOR . $path);
            $metas = $this->metasService->getMetas(DIRECTORY_SEPARATOR . $path);

            $title = current(array_filter($metas, function ($item){return $item->getMetaKey() == "title";}));
            $alt = current(array_filter($metas, function ($item){return $item->getMetaKey() == "alt";}));
            $description = current(array_filter($metas, function ($item){return $item->getMetaKey() == "description";}));
            $extra = current(array_filter($metas, function ($item){return !in_array($item->getMetaKey(), ["title","alt","description","dimensions"]); }));


            $list[] = [
                'name'                   => basename($file->path()),
                'type'                   => $mimeType,
                'size'                   => $file->fileSize(),
                'visibility'             => $file->visibility(),
                'path'                   => $this->resolveUrl($path),
                'storage_path'           => $path,
                'last_modified'          => $time,
                'last_modified_formated' => $this->getItemTime($time),
                'metas' => [
                    "title" => $title ? $title->getMetaValue() : null,
                    "alt" => $alt ? $alt->getMetaValue() : null,
                    "description" => $description ? $description->getMetaValue() : null,
                    "extra" => $extra,
                ]

            ];
        }

        return $list;
    }

    /**
     * rename item.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function getItemInfos(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $path = str_replace($this->baseUrl, "", $data["path"]);

        if ($path && !$this->filesystem->fileExists($path)) {
            return new JsonResponse([
                'error' => $this->translator->trans('error.doesnt_exist', ['attr' => $path] , "EasyMediaBundle"),
            ]);
        }

        $detector = new FinfoMimeTypeDetector();

        /** @var FileAttributes $file */
        $time = $this->filesystem->lastModified($path) ?? null;
        $mimeType = $detector->detectMimeTypeFromFile($this->rootPath . DIRECTORY_SEPARATOR . $path);
        $metas = $this->metasService->getMetas(DIRECTORY_SEPARATOR . $path);

        $title = current(array_filter($metas, function ($item){return $item->getMetaKey() == "title";}));
        $alt = current(array_filter($metas, function ($item){return $item->getMetaKey() == "alt";}));
        $description = current(array_filter($metas, function ($item){return $item->getMetaKey() == "description";}));
        $extra = current(array_filter($metas, function ($item){return !in_array($item->getMetaKey(), ["title","alt","description","dimensions"]); }));


        $item = [
            'name'                   => basename($path),
            'type'                   => $mimeType,
            'size'                   => $this->filesystem->fileSize($path),
            'visibility'             => $this->filesystem->visibility($path),
            'path'                   => $this->resolveUrl($path),
            'storage_path'           => $path,
            'last_modified'          => $time,
            'last_modified_formated' => $this->getItemTime($time),
            'metas' => [
                "title" => $title ? $title->getMetaValue() : null,
                "alt" => $alt ? $alt->getMetaValue() : null,
                "description" => $description ? $description->getMetaValue() : null,
                "extra" => $extra,
            ]

        ];

        return new JsonResponse($item);
    }

    /**
     * get directory data.
     *
     * @param mixed $folder
     * @param mixed $rec
     */
    protected function getFolderContent($folder, $rec = false)
    {
        /** @var DirectoryListing $contents */
        return $this->filesystem->listContents($folder, $rec)->toArray();
    }

    protected function ignoreFiles($item)
    {
        return !preg_grep($this->ignoreFiles, [basename($item->path())]);
    }

    /**
     * filter directory data by type.
     *
     * @param array $list
     * @param [type] $type
     */
    protected function getFolderListByType(array $list, $type)
    {
        $list = (new ArrayCollection($list))->filter(function ($item) use ($type){
            if($type == "dir"){
                return $item->isDir();
            }
            if($type == "file"){
                return $item->isFile();
            }
            return false;
        });

        $sortBy = $list->map(function ($item){
            return basename($item->path());
        })->toArray();

        $items  = $list->toArray();

        array_multisort($sortBy, SORT_NATURAL, $items);

        return $items;
    }

    /**
     * get folder size.
     *
     * @param [type] $list
     */
    protected function getFolderInfoFromList($list)
    {
        $list = (new ArrayCollection($list))->filter(function ($item){
            return $item->isFile();
        });

        return [
            'count' => $list->count(),
            'size'  => array_sum($list->map(function ($item){
                return $item->fileSize();
            })->toArray()),
        ];
    }
}
