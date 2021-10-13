<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Adeliom\EasyMediaBundle\Event\EasyMediaFileMoved;
use Illuminate\Support\Str;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
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
            $destination = $this->folder->find($destinationId);
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

            $repo = $this->medias;
            if ($file_type == 'folder'){
                $repo = $this->folder;
            }

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

                if($entity = $repo->find($id)){
                    // Move
                    try {
                        if($file_type == 'folder'){
                            $entity->setParent($destination);
                        }else{
                            $entity->setFolder($destination);
                        }
                        $this->em->persist($entity);
                        $this->em->flush();

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
