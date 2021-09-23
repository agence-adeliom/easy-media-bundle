<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCreateDirectory;
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

        $path            = $data["path"];
        $new_folder_name = $this->cleanName($data["new_folder_name"], true);
        $full_path       = !$path ? $new_folder_name : $this->clearDblSlash("$path/$new_folder_name");
        $message         = '';

        if ($this->filesystem->fileExists($full_path)) {
            $message = $this->translator->trans('error.already_exists', [] , "EasyMediaBundle");
        } else {
            try {
                $this->filesystem->createDirectory($full_path, [
                    "directory_visibility" => "public"
                ]);
            } catch (FilesystemException | UnableToCreateDirectory $exception) {
                $message = $this->translator->trans('error.creating_dir', [] , "EasyMediaBundle");
            }
        }

        return new JsonResponse(compact('message', 'new_folder_name'));
    }

}
