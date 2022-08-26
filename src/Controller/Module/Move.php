<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Adeliom\EasyMediaBundle\Event\EasyMediaFileMoved;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Move
{
    /**
     * move files/folders.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function moveItem(Request $request)
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $destinationId = $data['destination'];
        $movedFiles = $data['moved_files'];
        $destination = null;
        if (!empty($destinationId)) {
            $destination = $this->manager->getFolder($destinationId);
        }

        $result = [];
        $toBroadCast = [];

        foreach ($movedFiles as $one) {
            $id = $one['id'];
            $file_name = $one['name'];
            $file_type = $one['type'];
            $old_path = $one['storage_path'];
            $defaults = [
                'id' => $id,
                'type' => $file_type,
                'name' => $file_name,
                'old_path' => $old_path,
            ];

            $new_path = sprintf('/%s', $file_name);
            if ($destination) {
                $new_path = $destination->getPath().$new_path;
            }

            $defaults['new_path'] = $new_path;
            try {
                if ('folder' === $file_type && ($destination && $destination->getId() === $id)) {
                    throw new \Exception($this->translator->trans('error.move_into_self', [], 'EasyMediaBundle'));
                }

                $entity = 'folder' === $file_type ? $this->manager->getFolder($id) : $this->manager->getMedia($id);
                if ($entity) {
                    // Move
                    try {
                        if ('folder' === $file_type) {
                            $entity->setParent($destination);
                        } else {
                            $entity->setFolder($destination);
                        }

                        $this->manager->save($entity);

                        $result[] = array_merge($defaults, ['success' => true]);
                        $toBroadCast[] = $defaults;

                        // fire event
                        $this->eventDispatcher->dispatch(new EasyMediaFileMoved($defaults['old_path'], $defaults['new_path']), EasyMediaFileMoved::NAME);
                    } catch (\Exception $exception) {
                        throw new \Exception($this->translator->trans('error.moving', [], 'EasyMediaBundle'), $exception->getCode(), $exception);
                    }
                }
            } catch (\Exception $e) {
                $result[] = [
                    'success' => false,
                    'message' => sprintf('"%s" ', $old_path).$e->getMessage(),
                ];
            }
        }

        return new JsonResponse($result);
    }
}
