<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Adeliom\EasyMediaBundle\Exception\AlreadyExist;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait NewFolder
{
    /**
     * create new folder.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function createNewFolder(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $currentFolderId            = $data["folder"];
        if(!empty($currentFolderId)){
            $currentFolder = $this->manager->getFolder($currentFolderId);
        }else{
            $currentFolder = null;
        }

        $new_folder_name = $this->helper->cleanName($data["new_folder_name"], true);
        $message         = '';

        try {
            $this->manager->createFolder($new_folder_name, $currentFolder ? $currentFolder->getPath() : null);
        }catch (AlreadyExist $exception) {
            $message = $exception->getMessage();
        }catch (\Exception | FilesystemException $exception) {
            $message = $this->translator->trans('error.creating_dir', [] , "EasyMediaBundle");
        }

        return new JsonResponse(compact('message', 'new_folder_name'));
    }

}
