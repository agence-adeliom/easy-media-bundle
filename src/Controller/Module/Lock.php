<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Lock
{
    /**
     * get locked items & directories list.
     *
     * @param [type] $dirs
     */
    public function getLockList()
    {
        return new JsonResponse($this->lockList());
    }

    /**
     * get data.
     *
     * @param [type] $path
     */
    public function lockList()
    {
        return [
            'locked' => array_map(function ($lock){
                return $lock->getPath();
            }, $this->locker->findAll())
        ];
    }

    /**
     * lock/unlock files/folders.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function lockItem(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $lockedList = array_map(function ($lock){
            return $lock->getPath();
        }, $this->locker->findAll());

        $toRemove = [];
        $toAdd    = [];
        $result   = [];

        foreach ($data["list"] as $item) {
            $url  = $item['path'];
            $name = $item['name'];

            if (in_array($url, $lockedList)) {
                $toRemove[] = $url;
            } else {
                $toAdd[] = ['path' => $url];
            }

            $result[] = [
                'message' => $this->translator->trans('lock_success', ['attr' => $name], "EasyMediaBundle"),
            ];
        }

        if ($toRemove) {
            foreach ($this->locker->findByPath($toRemove) as $i){
                $this->em->remove($i);
            }
        }

        if ($toAdd) {
            $class = $this->lockEntity;
            foreach ($toAdd as $i){
                $instance = new $class();
                $instance->setPath($i['path']);
                $this->em->persist($instance);
            }
        }

        $this->em->flush();

        return new JsonResponse(compact('result'));
    }
}
