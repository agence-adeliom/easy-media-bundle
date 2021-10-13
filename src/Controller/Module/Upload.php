<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileSaved;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileUploaded;
use Embed\Embed;
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
            if($imageType = @exif_imagetype($url)){
                $random_name = filter_var($data["random_names"], FILTER_VALIDATE_BOOLEAN);
                $urlPath = parse_url($url, PHP_URL_PATH);
                $original  = substr($urlPath, strrpos($urlPath, '/') + 1);
                $name_only = pathinfo($original, PATHINFO_FILENAME);
                $file_type   = image_type_to_mime_type($imageType);
                $ext_only  = self::mime2ext($file_type);
                $name = $random_name ? $this->getRandomString() : $this->cleanName($name_only);
                $file_name =  $name. ".$ext_only";
                $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower($name))->toString() . ".$ext_only");
                $destination  = !$folder ? $final_name_slug : $this->clearDblSlash($upload_path . "/$final_name_slug");

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

                    dump($file);

                } catch (\Exception $e) {
                    $result = [
                        'success' => false,
                        'message' => $e->getMessage(),
                    ];
                }
            }else{
                try {
                    $embed = new Embed();
                    $infos = $embed->get($url);
                    dump($url, $infos);

                }catch (\RuntimeException $exception){
                    dump($exception);
                }
            }

            exit;


            try {


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

    public static function mime2ext($mime) {
        $mime_map = [
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpeg'                                                                => 'jpeg',
            'image/pjpeg'                                                               => 'jpeg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
        ];

        return isset($mime_map[$mime]) === true ? $mime_map[$mime] : false;
    }
}
