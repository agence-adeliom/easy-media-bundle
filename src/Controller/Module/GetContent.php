<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Doctrine\Common\Collections\ArrayCollection;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $folder = null;
        $path = '/';
        if (!empty($data['folder'])) {
            $folder = $this->manager->getFolder($data['folder']);
            if ($folder) {
                $path = $folder->getPath();
            }
        }
        if (empty($data['folder']) && !empty($data['path'])) {
            try {
                $folder = $this->manager->folderByPath($data['path']);
                if ($folder) {
                    $path = $folder->getPath();
                }
            } catch (FilesystemException $e) {
                return new JsonResponse([
                    'error' => $this->translator->trans($e->getMessage(), ['attr' => $path]),
                ]);
            }
        }
        if (!empty($data['folder']) && !$folder) {
            return new JsonResponse([
                'error' => $this->translator->trans('MediaManager::messages.error.doesnt_exist', ['attr' => $path]),
            ]);
        }

        return new JsonResponse([
            'files' => [
                'path' => $path,
                'items' => $this->paginate($this->getData($folder, $data['search'] ?? null), $this->paginationAmount),
            ],
        ]);
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
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $mediaId = $data['item'];

        if ($media = $this->helper->getMediaRepository()->findOneBy(['id' => $mediaId])) {
            /** @var Media $media */
            $path = $media->getPath();
            $time = $media->getLastModified() ?? null;
            $metas = $media->getMetas();

            $item = [
                'id' => $media->getId(),
                'name' => $media->getName(),
                'type' => $media->getMime(),
                'size' => $media->getSize(),
                'path' => $this->manager->publicUrl($media),
                'download_url' => $this->manager->downloadUrl($media),
                'storage_path' => $path,
                'last_modified' => $time,
                'last_modified_formated' => $this->helper->getItemTime($time),
                'metas' => $metas,
            ];

            return new JsonResponse($item);
        }

        return new JsonResponse([
            'error' => $this->translator->trans('error.doesnt_exist', ['attr' => $mediaId], 'EasyMediaBundle'),
        ]);
    }

    /**
     * get files list.
     *
     * @param mixed $dir
     */
    protected function getData(?Folder $dir, ?string $search = null)
    {
        $list = [];
        $dirList = $this->getFolderContent($dir, false, $search);
        $storageFolders = array_filter($this->getFolderListByType($dirList, 'dir'), [$this, 'ignoreFiles']);
        $storageFiles = array_filter($this->getFolderListByType($dirList, 'file'), [$this, 'ignoreFiles']);

        // folders
        foreach ($storageFolders as $folder) {
            /** @var Folder $folder */
            $path = $folder->getPath();
            $list[] = [
                'id' => $folder->getId(),
                'name' => $folder->getName(),
                'type' => 'folder',
                'path' => $folder->getPath(),
                'storage_path' => $path,
            ];
        }

        // files
        foreach ($storageFiles as $file) {
            /** @var Media $file */
            $path = $file->getPath();
            $time = $file->getLastModified() ?? null;
            $metas = $file->getMetas();

            $list[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'type' => $file->getMime(),
                'size' => $file->getSize(),
                'path' => $this->manager->publicUrl($file),
                'download_url' => $this->manager->downloadUrl($file),
                'storage_path' => $path,
                'last_modified' => $time,
                'last_modified_formated' => $this->helper->getItemTime($time),
                'metas' => $metas,
            ];
        }

        return $list;
    }

    /**
     * get directory data.
     */
    protected function getFolderContent($folder = null, bool $rec = false, ?string $search = null)
    {
        if (!empty($folder)) {
            /** @var Folder $folder */
            $folder = $this->manager->getFolder($folder);
        }

        $folderQuery = $this->helper->getFolderRepository()->createQueryBuilder('f');
        $mediaQuery = $this->helper->getMediaRepository()->createQueryBuilder('m');

        if($folder === null){
            $folderQuery->andWhere("f.parent IS NULL");
            $mediaQuery->andWhere("m.folder IS NULL");
        }else{
            $folderQuery->andWhere("f.parent = :folder")->setParameter('folder', $folder);
            $mediaQuery->andWhere("m.folder = :folder")->setParameter('folder', $folder);
        }

        if(!empty($search)){
            if(!$rec){
                $folderQuery->andWhere("f.name LIKE :search")->setParameter('search', '%'.trim($search).'%');
            }
            $mediaQuery->andWhere("m.name LIKE :search")->setParameter('search', '%'.trim($search).'%');
        }

        $folders = $folderQuery->getQuery()->getResult();
        $medias = $mediaQuery->getQuery()->getResult();

        $results = array_merge($folders, $medias);
        if($rec){
            foreach ($folders as $f){
                $results = array_merge($results, $this->getFolderContent($f, $rec, $search));
            }
        }

        if($rec){
            $results = array_filter($results, static function($item) {
                return $item instanceof Media;
            });
        }
        return $results;
    }

    protected function ignoreFiles($item)
    {
        return !preg_grep($this->ignoreFiles, [$item->getPath()]);
    }

    /**
     * filter directory data by type.
     *
     * @param [type] $type
     *
     * @return mixed[]
     */
    protected function getFolderListByType(array $list, $type)
    {
        $list = (new ArrayCollection($list))->filter(static function ($item) use ($type) {
            if ('dir' === $type) {
                return $item instanceof Folder;
            }

            if ('file' === $type) {
                return $item instanceof Media;
            }

            return false;
        });

        return $list->toArray();
    }

    /**
     * get folder size.
     *
     * @param [type] $list
     *
     * @return array<string, int>|array<string, float>
     */
    protected function getFolderInfoFromList($list)
    {
        $list = (new ArrayCollection($list))->filter(static fn ($item) => $item->isFile());

        return [
            'count' => $list->count(),
            'size' => array_sum($list->map(static fn ($item) => $item->fileSize())->toArray()),
        ];
    }
}
