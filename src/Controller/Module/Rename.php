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
        $message          = '';

        $repo = $this->medias;
        if ($type == 'folder'){
            $repo = $this->folder;
        }

        try {
            if ($entity = $repo->find($file["id"])) {
                try {
                    $old_path = $entity->getPath();
                    $new_path         = dirname($old_path) . "/$new_filename";


                    $entity->setName($new_filename);
                    $this->em->persist($entity);
                    $this->em->flush();

                    if ($type !== 'folder'){
                        if (!$this->filesystem->fileExists($new_path)) {
                            $this->filesystem->move($old_path, $new_path);
                            $this->eventDispatcher->dispatch(new EasyMediaFileRenamed($old_path, $new_path), EasyMediaFileRenamed::NAME);
                        } else {
                            throw new \Exception(
                                $this->translator->trans('error.already_exists', [] , "EasyMediaBundle")
                            );
                        }
                    }
                } catch (\Exception $exception) {
                    throw new \Exception(
                        $this->translator->trans('error.moving', [] , "EasyMediaBundle")
                    );
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        return new JsonResponse(compact('message', 'new_filename'));
    }
}
