<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Adeliom\EasyMediaBundle\Entity\Media;
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
}
