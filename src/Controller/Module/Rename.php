<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Adeliom\EasyMediaBundle\Event\EasyMediaFileRenamed;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Rename
{
    /**
     * rename item.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function renameItem(Request $request)
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $file = $data['file'];

        $type = $file['type'];
        $new_filename = $this->helper->cleanName($data['new_filename'], 'folder' === $type);
        $message = '';

        try {
            $object = 'folder' === $type ? $this->manager->getFolder($file['id']) : $this->manager->getMedia($file['id']);
            $old_filename = $object->getName();
            $object->setName($new_filename);
            $this->manager->save($object);
            $this->eventDispatcher->dispatch(new EasyMediaFileRenamed($old_filename, $new_filename), EasyMediaFileRenamed::NAME);
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
        }

        return new JsonResponse(['message' => $message, 'new_filename' => $new_filename]);
    }
}
