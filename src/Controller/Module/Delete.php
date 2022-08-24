<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Adeliom\EasyMediaBundle\Event\EasyMediaFileDeleted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Delete
{
    /**
     * delete files/folders.
     *
     * @param Request $request [description]
     *
     * @return JsonResponse [type] [description]
     */
    public function deleteItem(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $result = [];
        $toBroadCast = [];

        foreach ($data['deleted_files'] as $one) {
            $id = $one['id'];
            $name = $one['name'];
            $type = $one['type'];
            $item_path = $one['storage_path'];
            $defaults = [
                'id' => $id,
                'name' => $name,
                'type' => $type,
                'path' => $item_path,
            ];

            try {
                $entity = $type === 'folder' ? $this->manager->getFolder($id) : $this->manager->getMedia($id);

                if ($entity) {
                    $this->manager->delete($entity);

                    $result[] = array_merge($defaults, ['success' => true]);
                    $toBroadCast[] = $defaults;

                    $this->eventDispatcher->dispatch(new EasyMediaFileDeleted($item_path, $type === 'folder'), EasyMediaFileDeleted::NAME);
                }
            } catch (\Exception $exception) {
                $result[] = array_merge($defaults, [
                    'success' => false,
                    'message' => $this->translator->trans('error.deleting_file', [], 'EasyMediaBundle'),
                ]);
            }
        }

        return new JsonResponse($result);
    }
}
