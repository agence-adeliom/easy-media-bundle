<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToSetVisibility;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Visibility
{
    /**
     * change file visibility.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function changeItemVisibility(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $result      = [];
        $toBroadCast = [];

        foreach ($data["list"] as $file) {
            $name      = $file['name'];
            $type      = $file['visibility'] == 'public' ? 'private' : 'public';
            $file_path = $file['storage_path'];

            try {
                $this->filesystem->setVisibility($file_path, $type);
                $result[] = [
                    'success'    => true,
                    'name'       => $name,
                    'visibility' => $type,
                    'message'    => $this->translator->trans('visibility.success', ['attr' => $name], "EasyMediaBundle"),
                ];

                $toBroadCast[] = [
                    'name'       => $name,
                    'visibility' => $type,
                ];
            } catch (FilesystemException | UnableToSetVisibility $exception) {
                $result[] = [
                    'success' => false,
                    'message' => $this->translator->trans('visibility.error', ['attr' => $name], "EasyMediaBundle"),
                ];
            }
        }


        return new JsonResponse($result);
    }
}
