<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Doctrine\Common\Collections\ArrayCollection;
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
        if (!empty($data['folder']) && !$folder) {
            return new JsonResponse([
                'error' => $this->translator->trans('MediaManager::messages.error.doesnt_exist', ['attr' => $path]),
            ]);
        }

        return new JsonResponse([
            'files' => [
                'path' => $path,
                'items' => $this->paginate($this->getData($folder), $this->paginationAmount),
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
                'path' => $this->manager->publicUrl($file),
                'download_url' => $this->helper->downloadUrl($file, UrlGeneratorInterface::ABSOLUTE_URL),
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
    protected function getData(?Folder $dir)
    {
        $list = [];
        $dirList = $this->getFolderContent($dir);
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
                'download_url' => $this->helper->downloadUrl($file, UrlGeneratorInterface::ABSOLUTE_URL),
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
    protected function getFolderContent($folder, bool $rec = false)
    {
        if (!empty($folder)) {
            /** @var Folder $folder */
            $folder = $this->manager->getFolder($folder);
            $folders = $folder->getChildren();
            $medias = $folder->getMedias();

            return array_merge($folders->toArray(), $medias->toArray());
        }

        $folders = $this->helper->getFolderRepository()->findBy(['parent' => null]);
        $medias = $this->helper->getMediaRepository()->findBy(['folder' => null]);

        return array_merge($folders, $medias);
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
