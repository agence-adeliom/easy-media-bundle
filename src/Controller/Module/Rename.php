<?php
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
        $data = json_decode($request->getContent(), true);
        $file = $data["file"];

        $type             = $file['type'];
        $new_filename     = $this->helper->cleanName($data["new_filename"], $type == 'folder');
        $message          = '';

        try {
            if ($type == 'folder'){
                $object = $this->manager->getFolder($file["id"]);
            }else{
                $object = $this->manager->getMedia($file["id"]);
            }
            $old_filename = $object->getName();
            $object->setName($new_filename);
            $this->manager->save($object);
            $this->eventDispatcher->dispatch(new EasyMediaFileRenamed($old_filename, $new_filename), EasyMediaFileRenamed::NAME);

        } catch (\Exception $exception) {
            $message = $exception->getMessage();
        }

        return new JsonResponse(compact('message', 'new_filename'));
    }
}
