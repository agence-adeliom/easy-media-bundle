<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileSaved;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileUploaded;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Str;
use Symfony\Component\String\Slugger\AsciiSlugger;

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
            $folder = $this->folder->find($upload_folder_id);
        }
        $random_name = filter_var($request->request->get("random_names"), FILTER_VALIDATE_BOOLEAN);
        $custom_attr = json_decode($request->request->get("custom_attrs", '[]'), true);
        $result      = [];
        $upload_path = $folder ? $folder->getPath() : "";

        foreach ($request->files->get("file", []) as $one) {
            if ($this->allowUpload($one)) {
                $one        = $this->optimizeUpload($one);
                $orig_name  = $one->getClientOriginalName();
                $name_only  = pathinfo($orig_name, PATHINFO_FILENAME);
                $ext_only   = pathinfo($orig_name, PATHINFO_EXTENSION);

                $name = $random_name ? $this->getRandomString() : $this->cleanName($name_only);
                $final_name =  $name. ".$ext_only";
                $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower($name))->toString() . ".$ext_only");

                if(!empty($custom_attr)) {
                    $custom_attr = array_filter($custom_attr, function ($entry) use ($orig_name) {
                        return $entry["name"] == $orig_name;
                    });
                    $custom_attr = current($custom_attr);
                }
                $file_options = !empty($custom_attr) ? $custom_attr["options"] : [];
                $file_type    = $one->getMimeType();
                $destination  = !$folder ? $final_name_slug : $this->clearDblSlash($upload_path . "/$final_name_slug");
                try {
                    // check for mime type
                    if (Str::contains($file_type, $this->unallowedMimes)) {
                        throw new \Exception(
                            $this->translator->trans('not_allowed_file_ext', [] , "EasyMediaBundle")
                        );
                    }

                    // check for extension
                    if (Str::contains($ext_only, $this->unallowedExt)) {
                        throw new \Exception(
                            $this->translator->trans('not_allowed_file_ext', [] , "EasyMediaBundle")
                        );
                    }

                    // check existence
                    if ($this->filesystem->fileExists($destination)) {
                        throw new \Exception(
                            $this->translator->trans('error.already_exists', [] , "EasyMediaBundle")
                        );
                    }

                    // save file
                    $full_path = $this->storeFile($one, $upload_path, $final_name_slug);

                    /** @var Media $media */
                    $media = new $this->mediaEntity();
                    $media->setName($final_name);
                    $media->setSlug($final_name_slug);
                    $media->setFolder($folder);
                    $media->setMetas($file_options);
                    $media->setSize($full_path->getSize());
                    $media->setLastModified($full_path->getMTime());
                    $media->setMime($full_path->getMimeType());
                    $this->em->persist($media);
                    $this->em->flush();
                    // save metas
                    //$this->metasService->saveMetas($upload_path . DIRECTORY_SEPARATOR . $final_name, $file_options);

                    // fire event
                    $this->eventDispatcher->dispatch(new EasyMediaFileUploaded($full_path, $file_type, $file_options), EasyMediaFileUploaded::NAME);

                    $result[]  = [
                        'success'   => true,
                        'file_name' => $final_name,
                    ];
                } catch (\Exception $e) {
                    $result[] = [
                        'success' => false,
                        'message' => "\"$final_name\" " . $e->getMessage(),
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
            $type     = $data["mime_type"];
            $upload_folder_id     = $data["folder"];
            $folder = null;
            if(!empty($upload_folder_id)){
                $folder = $this->folder->find($upload_folder_id);
            }
            $upload_path = $folder ? $folder->getPath() : "";

            $original = $data["name"];
            $data     = explode(',', $data["data"])[1];

            $name_only   = pathinfo($original, PATHINFO_FILENAME) . '_' . $this->getRandomString();
            $ext_only    = pathinfo($original, PATHINFO_EXTENSION);
            $file_name   = "$name_only.$ext_only";
            $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower($name_only))->toString() . ".$ext_only");
            $destination  = !$folder ? $file_name : $this->clearDblSlash($upload_path . "/$final_name_slug");

            try {
                // check existence
                if ($this->filesystem->fileExists($destination)) {
                    throw new \Exception(
                        $this->translator->trans('error.already_exists', [] , "EasyMediaBundle")
                    );
                }

                // data is valid
                try {
                    $data = base64_decode($data);
                } catch (\Throwable $th) {
                    throw new \Exception(
                        $this->translator->trans('error.no_file', [] , "EasyMediaBundle")
                    );
                }

                $this->filesystem->write($destination, $data);

                // save file
                $file = new File($this->rootPath . DIRECTORY_SEPARATOR . $destination);
                /** @var Media $media */
                $media = new $this->mediaEntity();
                $media->setName($file_name);
                $media->setSlug($final_name_slug);
                $media->setFolder($folder);
                $media->setSize($file->getSize());
                $media->setLastModified($file->getMTime());
                $media->setMime($file->getMimeType());
                $this->em->persist($media);
                $this->em->flush();
                // fire event
                $this->eventDispatcher->dispatch(new EasyMediaFileSaved($destination, $type), EasyMediaFileSaved::NAME);
                $result = [
                    'success' => true,
                    'message' => $file_name,
                ];
            } catch (\Exception $e) {
                $result = [
                    'success' => false,
                    'message' => "\"$file_name\" " . $e->getMessage(),
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

            $url         = $data["url"];

            $upload_folder_id     = $data["folder"];
            $folder = null;
            if(!empty($upload_folder_id)){
                $folder = $this->folder->find($upload_folder_id);
            }
            $upload_path = $folder ? $folder->getPath() : "";

            $random_name = filter_var($data["random_names"], FILTER_VALIDATE_BOOLEAN);

            $original  = substr($url, strrpos($url, '/') + 1);
            $name_only = pathinfo($original, PATHINFO_FILENAME);
            $ext_only  = pathinfo($original, PATHINFO_EXTENSION);

            $name = $random_name ? $this->getRandomString() : $this->cleanName($name_only);
            $file_name =  $name. ".$ext_only";
            $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower($name))->toString() . ".$ext_only");
            $destination  = !$folder ? $final_name_slug : $this->clearDblSlash($upload_path . "/$final_name_slug");
            $file_type   = image_type_to_mime_type(@exif_imagetype($url));

            try {
                $ignore = array_merge($this->unallowedMimes, ['application/octet-stream']);

                // check for mime type
                if (Str::contains($file_type, $ignore)) {
                    throw new \Exception(
                        $this->translator->trans('not_allowed_file_ext', [] , "EasyMediaBundle")
                    );
                }

                // check existence
                if ($this->filesystem->fileExists($destination)) {
                    throw new \Exception(
                        $this->translator->trans('error.already_exists', [] , "EasyMediaBundle")
                    );
                }
                dump($url);
                // data is valid
                try {
                    $data = file_get_contents($url);
                } catch (\Throwable $th) {
                    throw new \Exception(
                        $this->translator->trans('error.no_file', [] , "EasyMediaBundle")
                    );
                }

                // save file
                $this->filesystem->write($destination, $data);

                $file = new File($this->rootPath . DIRECTORY_SEPARATOR . $destination);
                /** @var Media $media */
                $media = new $this->mediaEntity();
                $media->setName($file_name);
                $media->setSlug($final_name_slug);
                $media->setFolder($folder);
                $media->setSize($file->getSize());
                $media->setLastModified($file->getMTime());
                $media->setMime($file->getMimeType());
                $this->em->persist($media);
                $this->em->flush();

                // fire event
                $this->eventDispatcher->dispatch(new EasyMediaFileSaved($destination, $file_type), EasyMediaFileSaved::NAME);


                $result = [
                    'success' => true,
                    'message' => $file_name,
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
     * save file to disk.
     *
     * @param UploadedFile $file
     * @param $upload_path
     * @param $file_name
     * @return File $file path
     */
    protected function storeFile(UploadedFile $file, $upload_path, $file_name)
    {
        $upload_path = $this->rootPath . DIRECTORY_SEPARATOR . $upload_path;
        return $file->move($upload_path, $file_name);
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
