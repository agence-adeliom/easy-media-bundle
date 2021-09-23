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
        $copy = $data["use_copy"];
        $destination = $data["destination"];
        $movedFiles = $data["moved_files"];

        $result      = [];
        $toBroadCast = [];

        foreach ($movedFiles as $one) {
            $file_name = $one['name'];
            $file_type = $one['type'];
            $old_path  = $one['storage_path'];
            $defaults  = [
                'name'     => $file_name,
                'old_path' => $old_path,
            ];

            $new_path = "$destination/$file_name";

            try {
                if ($file_type == 'folder' && Str::startsWith($destination, "/$old_path")) {
                    throw new \Exception(
                        $this->translator->trans('error.move_into_self', [] , "EasyMediaBundle")
                    );
                }

                if (!$this->filesystem->fileExists($new_path)) {
                    // copy
                    if ($copy) {
                        // folders
                        if ($file_type == 'folder') {
                            try {
                                $this->filesystem->copy($old_path, $new_path);
                                $result[] = array_merge($defaults, ['success' => true]);
                            }catch (FilesystemException | UnableToCopyFile $exception){
                                throw new \Exception(
                                    $this->translator->trans('error.moving', [] , "EasyMediaBundle")
                                );
                            }
                        }

                        // files
                        else {
                            try {
                                $this->filesystem->copy($old_path, $new_path);
                                $this->metasService->moveMetas($old_path, $new_path);

                                $result[] = array_merge($defaults, ['success' => true]);
                            }catch (FilesystemException | UnableToCopyFile $exception){
                                throw new \Exception(
                                    $this->translator->trans('error.moving', [] , "EasyMediaBundle")
                                );
                            }
                        }
                    }

                    // move
                    else {
                        try {
                            $this->filesystem->move($old_path, $new_path);
                            $this->metasService->moveMetas($old_path, $new_path);

                            $result[]      = array_merge($defaults, ['success' => true]);
                            $toBroadCast[] = $defaults;

                            // fire event
                            $this->eventDispatcher->dispatch(new EasyMediaFileMoved($old_path, $new_path), EasyMediaFileMoved::NAME);

                        }catch (FilesystemException | UnableToMoveFile $exception){
                            throw new \Exception(
                                $this->translator->trans('error.moving', [] , "EasyMediaBundle")
                            );
                        }
                    }
                } else {
                    throw new \Exception(
                        $this->translator->trans('error.already_exists', [] , "EasyMediaBundle")
                    );
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
