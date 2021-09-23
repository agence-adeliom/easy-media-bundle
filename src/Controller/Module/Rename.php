<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Adeliom\EasyMediaBundle\Event\EasyMediaFileRenamed;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToMoveFile;
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
        $new_filename     = $this->cleanName($data["new_filename"], $type == 'folder');
        $old_path         = $file['storage_path'];
        $new_path         = dirname($old_path) . "/$new_filename";
        $message          = '';

        try {
            if (!$this->filesystem->fileExists($new_path)) {
                try {
                    $this->filesystem->move($old_path, $new_path);
                    $this->metasService->moveMetas($old_path, $new_path);

                    $this->eventDispatcher->dispatch(new EasyMediaFileRenamed($old_path, $new_path), EasyMediaFileRenamed::NAME);
                } catch (FilesystemException | UnableToMoveFile $exception) {
                    throw new \Exception(
                        $this->translator->trans('error.moving', [] , "EasyMediaBundle")
                    );
                }
            } else {
                throw new \Exception(
                    $this->translator->trans('error.already_exists', [] , "EasyMediaBundle")
                );
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        return new JsonResponse(compact('message', 'new_filename'));
    }
}
