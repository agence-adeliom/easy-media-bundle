<?php
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
    public function deleteItem(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $result      = [];
        $toBroadCast = [];

        foreach ($data["deleted_files"] as $one) {
            $name      = $one['name'];
            $type      = $one['type'];
            $item_path = $one['storage_path'];
            $defaults  = [
                'name' => $name,
                'path' => $item_path,
            ];

            try {
                if($type == 'folder'){
                    $this->filesystem->deleteDirectory($item_path);
                }else{
                    $this->filesystem->delete($item_path);
                    $this->metasService->removeMetas($item_path);
                }

                $result[]      = array_merge($defaults, ['success' => true]);
                $toBroadCast[] = $defaults;

                // fire event
                $this->eventDispatcher->dispatch(new EasyMediaFileDeleted($item_path, $type == 'folder'), EasyMediaFileDeleted::NAME);

            }catch (\Exception $e){
                $result[] = array_merge($defaults, [
                    'success' => false,
                    'message' => $this->translator->trans('error.deleting_file', [] , "EasyMediaBundle"),
                ]);
            }
        }

        return new JsonResponse($result);
    }
}
