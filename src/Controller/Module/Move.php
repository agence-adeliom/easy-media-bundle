<?php
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
        $data = json_decode($request->getContent(), true);
        $destinationId = $data["destination"];
        $movedFiles = $data["moved_files"];
        $destination = null;
        if(!empty($destinationId)){
            $destination = $this->manager->getFolder($destinationId);
        }
        $result      = [];
        $toBroadCast = [];

        foreach ($movedFiles as $one) {
            $id        = $one['id'];
            $file_name = $one['name'];
            $file_type = $one['type'];
            $old_path  = $one['storage_path'];
            $defaults  = [
                'id' => $id,
                'type' => $file_type,
                'name'     => $file_name,
                'old_path' => $old_path,
            ];



            $new_path = "/$file_name";
            if ($destination){
                $new_path = $destination->getPath() . $new_path;
            }
            $defaults["new_path"] = $new_path;
            try {
                if ($file_type == 'folder' && ($destination && $destination->getId() == $id)) {
                    throw new \Exception(
                        $this->translator->trans('error.move_into_self', [] , "EasyMediaBundle")
                    );
                }
                if ($file_type == 'folder'){
                    $entity = $this->manager->getFolder($id);
                }else{
                    $entity = $this->manager->getMedia($id);
                }
                if($entity){
                    // Move
                    try {
                        if($file_type == 'folder'){
                            $entity->setParent($destination);
                        }else{
                            $entity->setFolder($destination);
                        }
                        $this->manager->save($entity);

                        $result[]      = array_merge($defaults, ['success' => true]);
                        $toBroadCast[] = $defaults;

                        // fire event
                        $this->eventDispatcher->dispatch(new EasyMediaFileMoved($defaults["old_path"], $defaults["new_path"]), EasyMediaFileMoved::NAME);
                    }catch (\Exception $exception){
                        throw new \Exception(
                            $this->translator->trans('error.moving', [] , "EasyMediaBundle")
                        );
                    }
                }
            } catch (\Exception $e) {
                $result[]  = [
                    'success' => false,
                    'message' => "\"$old_path\" " . $e->getMessage(),
                ];
            }
        }


        return new JsonResponse($result);
    }
}
