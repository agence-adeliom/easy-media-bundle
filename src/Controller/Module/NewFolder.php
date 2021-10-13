<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Adeliom\EasyMediaBundle\Entity\Folder;
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
            $currentFolder = $this->folder->find($currentFolderId);
        }else{
            $currentFolder = null;
        }

        $new_folder_name = $this->cleanName($data["new_folder_name"], true);
        $message         = '';

        if (!empty($this->folder->findBy(["parent" => $currentFolder, "name" => $new_folder_name]))) {
            $message = $this->translator->trans('error.already_exists', [] , "EasyMediaBundle");
        } else {
            try {
                /** @var Folder $folder */
                $folder = new $this->folderEntity();
                $folder->setName($new_folder_name);
                if($currentFolder){
                    $folder->setParent($currentFolder);
                }
                $this->em->persist($folder);
                $this->em->flush();
            } catch (\Exception $exception) {
                $message = $this->translator->trans('error.creating_dir', [] , "EasyMediaBundle");
            }
        }

        return new JsonResponse(compact('message', 'new_folder_name'));
    }

}
