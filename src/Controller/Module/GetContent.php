<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
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
        $folder = $data["folder"];
        $path = '/';
        if(!empty($folder)){
            $folder = $this->folder->find($folder);
            if($folder){
                $path = $folder->getPath();
            }
        }
        if (!empty($data["folder"])  && !$folder) {
            return new JsonResponse([
                'error' => $this->translator->trans('MediaManager::messages.error.doesnt_exist', ['attr' => $path]),
            ]);
        }
        return new JsonResponse([
                'files' => [
                    'path'  => $path,
                    'items' => $this->paginate($this->getData($folder), $this->paginationAmount),
                ],
            ]);
    }

    /**
     * get files list.
     *
     * @param mixed $dir
     */
    protected function getData(?Folder $dir)
    {
        $list           = [];
        $dirList        = $this->getFolderContent($dir);
        $storageFolders = array_filter($this->getFolderListByType($dirList, 'dir'), [$this, 'ignoreFiles']);
        $storageFiles   = array_filter($this->getFolderListByType($dirList, 'file'), [$this, 'ignoreFiles']);

        // folders
        foreach ($storageFolders as $folder) {
            /** @var Folder $folder */
            $path = $folder->getPath();
            $list[] = [
                'id'                     => $folder->getId(),
                'name'                   => $folder->getName(),
                'type'                   => 'folder',
                'path'                   => $this->resolveUrl($path),
                'storage_path'           => $path,
            ];
        }


        // files
        foreach ($storageFiles as $file) {
            /** @var Media $file */
            $path = $file->getPath();
            $time = $file->getLastModified() ?? null;
            $metas = $file->getMetas();

            $list[] = [
                'id'                     => $file->getId(),
                'name'                   => $file->getName(),
                'type'                   => $file->getMime(),
                'size'                   => $file->getSize(),
                'path'                   => $this->resolveUrl($path),
                'storage_path'           => $path,
                'last_modified'          => $time,
                'last_modified_formated' => $this->getItemTime($time),
                'metas' => $metas
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
     * @param int $folder
     * @param mixed $rec
     */
    protected function getFolderContent($folder, $rec = false)
    {
        if (!empty($folder)){
            /** @var Folder $folder */
            $folder = $this->folder->find($folder);
            $folders = $folder->getChildren();
            $medias = $folder->getMedias();
            return array_merge($folders->toArray(), $medias->toArray());
        }else{
            $folders = $this->folder->findBy(["parent" => null]);
            $medias = $this->medias->findBy(["folder" => null]);
            return array_merge($folders, $medias);
        }
    }

    protected function ignoreFiles($item)
    {
        return !preg_grep($this->ignoreFiles, [$item->getPath()]);
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
                return $item instanceof Folder;
            }
            if($type == "file"){
                return $item instanceof Media;
            }
            return false;
        });

        $items  = $list->toArray();
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
