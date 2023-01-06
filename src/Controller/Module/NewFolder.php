<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Adeliom\EasyMediaBundle\Exception\AlreadyExist;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait NewFolder
{
    /**
     * create new folder.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function createNewFolder(Request $request)
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $currentFolderId = $data['folder'];
        $currentFolder = empty($currentFolderId) ? null : $this->manager->getFolder($currentFolderId);

        $new_folder_name = $this->helper->cleanName($data['new_folder_name'], true);
        $message = '';

        try {
            $this->manager->createFolder($new_folder_name, $currentFolder?->getPath());
        } catch (AlreadyExist $alreadyExist) {
            $message = $alreadyExist->getMessage();
        } catch (\Exception|FilesystemException $exception) {
            dump($exception);
            $message = $this->translator->trans('error.creating_dir', [], 'EasyMediaBundle');
        }

        return new JsonResponse(['message' => $message, 'new_folder_name' => $new_folder_name]);
    }
}
