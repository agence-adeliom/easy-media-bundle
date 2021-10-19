<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Adeliom\EasyMediaBundle\Event\EasyMediaFileSaved;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileUploaded;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Upload
{
    /**
     * upload new files.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function upload(Request $request)
    {
        $upload_folder_id = $request->request->get("upload_folder");
        $folder = null;
        if(!empty($upload_folder_id)){
            $folder = $this->manager->getFolder($upload_folder_id);
        }

        $random_name = filter_var($request->request->get("random_names"), FILTER_VALIDATE_BOOLEAN);
        $custom_attr = json_decode($request->request->get("custom_attrs", '[]'), true);
        $result      = [];

        foreach ($request->files->get("file", []) as $one) {
            if ($this->allowUpload($one)) {

                try {
                    $one        = $this->optimizeUpload($one);
                    $orig_name  = $one->getClientOriginalName();
                    $name = $random_name ? $this->helper->getRandomString() : null;

                    if(!empty($custom_attr)) {
                        $custom_attr = array_filter($custom_attr, function ($entry) use ($orig_name) {
                            return $entry["name"] == $orig_name;
                        });
                        $custom_attr = current($custom_attr);
                    }
                    $file_options = !empty($custom_attr) ? $custom_attr["options"] : [];

                    $media = $this->manager->createMedia($one, $folder ? $folder->getPath() : null, $name);
                    $media->setMetas(array_merge($media->getMetas(), $file_options));
                    $this->manager->save($media);

                    $this->eventDispatcher->dispatch(new EasyMediaFileUploaded($media->getPath(), $media->getMime(), $media->getMetas()), EasyMediaFileUploaded::NAME);

                    $result[]  = [
                        'success'   => true,
                        'file_name' => $media->getName(),
                    ];
                } catch (\Exception $e) {
                    $result[] = [
                        'success' => false,
                        'message' => $e->getMessage(),
                    ];
                }
            } else {
                $result[] = [
                    'success' => false,
                    'message' => $this->translator->trans('error.cant_upload', [] , "EasyMediaBundle"),
                ];
            }
        }


        return new JsonResponse($result);
    }

    /**
     * save cropped image.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function uploadEditedImage(Request $request)
    {
        if ($this->allowUpload()) {

            $data = json_decode($request->getContent(), true);

            $upload_folder_id = $data["folder"];
            $folder = null;
            if(!empty($upload_folder_id)){
                $folder = $this->manager->getFolder($upload_folder_id);
            }
            $upload_path = $folder ? $folder->getPath() : null;
            $original = $data["name"];
            $name_only   = pathinfo($original, PATHINFO_FILENAME) . '_' . $this->helper->getRandomString();


            try {
                $media = $this->manager->createMedia($data["data"], $upload_path, $name_only);
                $this->eventDispatcher->dispatch(new EasyMediaFileSaved($media->getPath(), $media->getMime()), EasyMediaFileSaved::NAME);
                $result = [
                    'success' => true,
                    'message' => $media->getName(),
                ];
            } catch (\Exception $e) {
                $result = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => $this->translator->trans('error.cant_upload', [] , "EasyMediaBundle"),
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * save image from link.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function uploadLink(Request $request)
    {
        if ($this->allowUpload()) {
            $data = json_decode($request->getContent(), true);
            $url = $data["url"];
            $upload_folder_id = $data["folder"];
            $folder = null;
            if(!empty($upload_folder_id)){
                $folder = $this->manager->getFolder($upload_folder_id);
            }

            try {

                $random_name = filter_var($data["random_names"], FILTER_VALIDATE_BOOLEAN);
                $name = $random_name ? $this->helper->getRandomString() : null;

                $media =  $this->manager->createMedia($url, $folder ? $folder->getPath() : null, $name);
                $this->eventDispatcher->dispatch(new EasyMediaFileSaved($media->getPath(), $media->getMime()), EasyMediaFileSaved::NAME);

                $result = [
                    'success' => true,
                    'message' => $media->getName(),
                ];
            }catch (\Exception $e){
                $result = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

        } else {
            $result = [
                'success' => false,
                'message' => $this->translator->trans('error.cant_upload', [] , "EasyMediaBundle"),
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * allow/disallow user upload.
     *
     * @param null $file
     * @return bool [boolean]
     */
    protected function allowUpload($file = null)
    {
        return true;
    }

    /**
     * do something to file b4 its saved to the server.
     *
     * @param UploadedFile $file
     * @return UploadedFile $file
     */
    protected function optimizeUpload(UploadedFile $file)
    {
        return $file;
    }


}
