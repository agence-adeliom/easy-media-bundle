<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Event\EasyMediaGenerateAllAlt;
use Adeliom\EasyMediaBundle\Event\EasyMediaGenerateAlt;
use Adeliom\EasyMediaBundle\Event\EasyMediaGenerateAltGroup;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Metas
{
    /**
     * rename item.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function editMetasItem(Request $request)
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $file = $data['file'];
        $metas = $data['new_metas'];
        $message = '';

        try {
            /** @var Media $object */
            $object = $this->manager->getMedia($file['id']);
            $object->setMetas(array_merge($object->getMetas(), $metas));
            $this->manager->save($object);
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
        }

        return new JsonResponse(['message' => $message, 'metas' => $metas]);
    }



    /**
     * Dispatch an event to allow to generate an alt for the selected file
     *
     * @param Request $request the AJAX request on submit
     */
    public function generateAltItem(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $file = $data['file'];
        $error = '';

        try {
            /** @var Media $object */
            $object = $this->manager->getMedia($file['id']);
            $metas = $object->getMetas();
            $oldAlt = $metas['alt'];

            $event = $this->eventDispatcher->dispatch(
                new EasyMediaGenerateAlt($object, $data['path'] ?? '', $oldAlt),
                EasyMediaGenerateAlt::NAME
            );
            $newAlt = $event->getAlt();
            if (!empty($newAlt) && $newAlt !== $oldAlt) {
                $metas['alt'] = $newAlt;
                $object->setMetas(array_merge($object->getMetas(), $metas));
                $this->manager->save($object);

                return new JsonResponse(['error' => $error, 'alt' => $newAlt]);
            }
        } catch (\Exception $exception) {
            $error = $exception->getMessage();
        }

        return new JsonResponse(['error' => $error, 'alt' => $oldAlt]);
    }

    /**
     * Dispatch an event to allow to generate all alt for a group of medias
     *
     * @param Request $request the AJAX request on submit
     */
    public function generateAltGroup(Request $request): JsonResponse
    {
        try {
            $files = json_decode($request->getContent(), true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
            $this->eventDispatcher->dispatch(
                new EasyMediaGenerateAltGroup($files['files']),
                EasyMediaGenerateAltGroup::NAME
            );
            return new JsonResponse(['error' => null, 'data' => 'generating']);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage(), 'data' => '']);
        }
    }

    /**
     * Dispatch an event to allow to generate all alt for all media
     *
     * @param Request $request the AJAX request on submit
     */
    public function generateAllAlt(Request $request): JsonResponse
    {
        try {
            $this->eventDispatcher->dispatch(new EasyMediaGenerateAllAlt($request), EasyMediaGenerateAllAlt::NAME);
            return new JsonResponse(['error' => null, 'data' => 'generating']);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage(), 'data' => '']);
        }
    }
}
