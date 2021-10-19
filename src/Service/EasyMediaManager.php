<?php
namespace Adeliom\EasyMediaBundle\Service;


use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Exception\AlreadyExist;
use Adeliom\EasyMediaBundle\Exception\ExtNotAllowed;
use Adeliom\EasyMediaBundle\Exception\FolderNotExist;
use Adeliom\EasyMediaBundle\Exception\NoFile;
use Adeliom\EasyMediaBundle\Exception\ProviderNotFound;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Embed\Embed;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToRetrieveMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

class EasyMediaManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ContainerBagInterface
     */
    protected $parameters;

    /**
     * @var EasyMediaHelper
     */
    protected $helper;

    protected $rootPath;

    public function __construct(EasyMediaFilesystem $filesystemManager, EasyMediaHelper $helper, EntityManagerInterface $em, ContainerBagInterface $parameters, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->helper = $helper;
        $this->parameters = $parameters;
        $this->translator = $translator;
        $this->rootPath = $helper->getRootPath();
        $this->filesystem = $filesystemManager->getFilesystem();
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * @return EasyMediaHelper
     */
    public function getHelper(): EasyMediaHelper
    {
        return $this->helper;
    }

    /**
     * @param $id
     * @return mixed|object|null
     */
    public function getFolder($id)
    {
        return $this->getHelper()->getFolderRepository()->find($id);
    }

    /**
     * @param string|null $path
     * @return false|mixed|object|null
     */
    public function folderByPath(?string $path)
    {
        $dir = str_replace("//", "/", $this->rootPath . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR);
        if(is_dir($dir)){
            $slugs = array_values(array_filter(explode('/', $path)));
            $parent = null;
            foreach ($slugs as $i => $slug){
                if($folder = $this->getHelper()->getFolderRepository()->findOneBy([
                    'parent' => $parent,
                    'slug' => $slug,
                ])){
                    $parent = $folder;
                }
                if($i == count($slugs) -1){
                    return $folder ?: false;
                }
            }
            return $parent;
        }
        return false;
    }

    /**
     * @param $id
     * @return mixed|object|null
     */
    public function getMedia($id)
    {
        return $this->getHelper()->getMediaRepository()->find($id);
    }

    /**
     * @throws FilesystemException|FolderNotExist
     */
    public function createFolder(?string $name, $path = null) : Folder
    {
        $class = $this->getHelper()->getFolderClassName();
        /** @var Folder $entity */
        $entity = new $class();

        if($name) $entity->setName($name);

        $folder = $this->folderByPath($path);
        if($folder === false) {
            throw new FolderNotExist("The folder does not exist");
        }

        if (!empty($this->getHelper()->getFolderRepository()->findBy(["parent" => $folder, "name" => $name]))) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [] , "EasyMediaBundle"));
        }

        $entity->setParent($folder);
        $this->filesystem->createDirectory($entity->getPath());
        $this->save($entity);
        return $entity;
    }

    public function createMedia($source, $path = null, $name = null) : Media
    {
        $class = $this->getHelper()->getMediaClassName();
        /** @var Media $entity */
        $entity = new $class();

        if($name) {
            $entity->setName($this->helper->cleanName($name));
        };
        $folder = $this->folderByPath($path);
        if($folder === false) {
            throw new FolderNotExist("The folder does not exist");
        }
        $entity->setFolder($folder);

        if (substr($source, 0, 5) == 'data:') {
            $entity = $this->createFromBase64($entity, $source);
        }else if (filter_var($source, FILTER_VALIDATE_URL) !== FALSE) {
            if($imageType = @exif_imagetype($source)){
                $entity = $this->createFromImageURL($entity, $source, $imageType);
            }else{
                $entity = $this->createFromOembed($entity, $source);
            }
        }else{
            $entity = $this->createFromFile($entity, $source);
        }

        $this->save($entity);

        return $entity;
    }

    private function createFromOembed(Media $entity, $source){

        $embed = new Embed();
        $infos = $embed->get($source);

        if($infos->getOEmbed() && !empty($infos->getOEmbed()->all())){
            $oembed = $infos->getOEmbed();
            $name = $entity->getName() ?: $oembed->get('title');

            $entity->setName($name);
            $entity->setMime("application/json+oembed");
            $entity->setMetas([
                "provider" => [
                    "name" => $infos->providerName,
                    "url" => (string) $infos->providerUrl
                ],
                "author" => [
                    "name" => $infos->authorName,
                    "url" => (string) $infos->authorUrl
                ],
                "url" => (string) $infos->url,
                "image" => (string) $infos->image,
                "icon" => (string) ($infos->icon ?: $infos->favicon),
                "type" => $oembed->get('type'),
                'code' => [
                    'html' => $infos->code ? $infos->code->html : null,
                    'width' => $infos->code ? $infos->code->width : null,
                    'height' => $infos->code ? $infos->code->height : null,
                    'ratio' => $infos->code ? $infos->code->ratio : null
                ]
            ]);
        }else{
            throw new ProviderNotFound(
                $this->translator->trans('error.provider_not_found', [] , "EasyMediaBundle")
            );
        }

        return $entity;
    }

    private function createFromBase64(Media $entity, $source){

        if(preg_match('/^data\:([a-zA-Z]+\/[a-zA-Z]+);base64\,([a-zA-Z0-9\+\/]+\=*)$/', $source, $matches)) {
            $infos = [
                'mime' => $matches[1],
                'data' => base64_decode($matches[2]),
            ];
        }else{
            throw new NoFile(
                $this->translator->trans('error.no_file', [] , "EasyMediaBundle")
            );
        }
        $filename = strtolower((new AsciiSlugger())->slug(strtolower($entity->getName()))->toString() . "." . EasyMediaHelper::mime2ext($infos["mime"]));
        $entity->setSlug($filename);

        $path = $entity->getFolder() ? $entity->getFolder()->getPath() : null;
        $destination = $this->helper->clearDblSlash($path . DIRECTORY_SEPARATOR . $filename);
        if ($this->filesystem->fileExists($destination)) {
            throw new AlreadyExist(
                $this->translator->trans('error.already_exists', [] , "EasyMediaBundle")
            );
        }

        $this->filesystem->write($destination, $infos["data"]);

        $file = new File($this->helper->clearDblSlash($this->rootPath . DIRECTORY_SEPARATOR . $destination));

        if(@exif_imagetype($file->getPathname())){
            list($width, $height) = getimagesize($file->getPathname());
            $entity->setMetas([
                "dimensions" => [
                    "width" => $width,
                    "height" => $height,
                    "ratio" => ($height / $width) * 100,
                ]
            ]);
        }

        $entity->setSize($file->getSize());
        $entity->setLastModified($file->getMTime());
        $entity->setMime($file->getMimeType());

        return $entity;
    }

    private function createFromImageURL(Media $entity, $source, $type){

        $urlPath = parse_url($source, PHP_URL_PATH);
        $original  = substr($urlPath, strrpos($urlPath, '/') + 1);
        $name = $entity->getName() ?: pathinfo($original, PATHINFO_FILENAME);

        $file_type   = image_type_to_mime_type($type);
        $ext_only  = EasyMediaHelper::mime2ext($file_type);

        $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower($name))->toString() . ".$ext_only");
        $entity->setSlug($final_name_slug);

        $ignore = array_merge($this->parameters->get("easy_media.unallowed_mimes"), ['application/octet-stream']);

        // check for mime type
        if (Str::contains($file_type, $ignore)) {
            throw new ExtNotAllowed(
                $this->translator->trans('not_allowed_file_ext', [] , "EasyMediaBundle")
            );
        }
        // check existence
        if ($this->filesystem->fileExists($entity->getPath())) {
            throw new AlreadyExist(
                $this->translator->trans('error.already_exists', [] , "EasyMediaBundle")
            );
        }

        try {
            $data = file_get_contents($source);
        } catch (\Throwable $th) {
            throw new NoFile(
                $this->translator->trans('error.no_file', [] , "EasyMediaBundle")
            );
        }

        $this->filesystem->write($entity->getPath(), $data);

        $file = new File($this->helper->clearDblSlash($this->rootPath . DIRECTORY_SEPARATOR . $entity->getPath()));

        if(empty($entity->getName())){
            $entity->setName($file->getFilename());
        }

        list($width, $height) = getimagesize($file->getPathname());

        $entity->setMetas([
            "dimensions" => [
                "width" => $width,
                "height" => $height,
                "ratio" => ($height / $width) * 100,
            ]
        ]);

        $entity->setSize($file->getSize());
        $entity->setLastModified($file->getMTime());
        $entity->setMime($file->getMimeType());

        return $entity;
    }

    private function createFromFile(Media $entity, $source){

        if (is_string($source)){
            $source = new File($source);
        }
        if (!($source instanceof File)){
            throw new NoFile();
        }

        if($source instanceof UploadedFile){
            $orig_name  = $source->getClientOriginalName();
            $name = $entity->getName() ?: pathinfo($orig_name, PATHINFO_FILENAME);
            $ext_only   = pathinfo($orig_name, PATHINFO_EXTENSION);
            if($type = $source->getClientMimeType()){
                $entity->setMime($type);
                if($ext = EasyMediaHelper::mime2ext($type)){
                    $ext_only = $ext;
                }
            }
            if(empty($entity->getName())){
                $entity->setName($source->getClientOriginalName());
            }
        }else{
            $orig_name  = $source->getFilename();
            $name = $entity->getName() ?: $source->getBasename('.'.$source->getExtension());
            $ext_only   = pathinfo($orig_name, PATHINFO_EXTENSION);
            if($type = $source->getMimeType()){
                $entity->setMime($type);
                if($ext = EasyMediaHelper::mime2ext($type)){
                    $ext_only = $ext;
                }
            }
            if(empty($entity->getName())){
                $entity->setName($source->getFilename());
            }
        }

        $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower($name))->toString() . ".$ext_only");

        $entity->setSlug($final_name_slug);
        $entity->setSize($source->getSize());
        $entity->setLastModified($source->getMTime());

        // check for mime type
        if (Str::contains($entity->getMime(), $this->parameters->get("easy_media.unallowed_mimes"))) {
            throw new ExtNotAllowed(
                $this->translator->trans('not_allowed_file_ext', [] , "EasyMediaBundle")
            );
        }

        // check for extension
        if (Str::contains($ext_only, $this->parameters->get("easy_media.unallowed_ext"))) {
            throw new ExtNotAllowed(
                $this->translator->trans('not_allowed_file_ext', [] , "EasyMediaBundle")
            );
        }

        // check existence
        if ($this->filesystem->fileExists($this->helper->clearDblSlash($entity->getPath()))) {
            throw new AlreadyExist(
                $this->translator->trans('error.already_exists', [] , "EasyMediaBundle")
            );
        }

        if(@exif_imagetype($source->getPathname())){
            list($width, $height) = getimagesize($source->getPathname());
            $entity->setMetas([
                "dimensions" => [
                    "width" => $width,
                    "height" => $height,
                    "ratio" => ($height / $width) * 100,
                ]
            ]);
        }

        try {
            $destination = $this->helper->clearDblSlash($this->rootPath . DIRECTORY_SEPARATOR . dirname($entity->getPath()));
            if($source instanceof UploadedFile){
                $source->move($destination, $entity->getSlug());
            }elseif($source instanceof File){
                if(!copy($source->getPathname(), $this->helper->clearDblSlash($destination . DIRECTORY_SEPARATOR . $entity->getSlug()))){
                    throw new UnableToCopyFile();
                }
            }
        }catch (FileException | UnableToCopyFile $exception){
            dump($exception);
        }

        return $entity;
    }

    public function delete($item, $flush = true)
    {
        $this->em->remove($item);

        if ($item instanceof Folder) {
            $this->filesystem->deleteDirectory($item->getPath());
        }

        if ($item instanceof Media) {
            $this->filesystem->delete($item->getPath());
        }

        if ($flush){
            $this->em->flush();
        }
    }

    public function save($item, $flush = true)
    {
        $this->em->persist($item);
        if ($flush){
            $this->em->flush();
        }
    }

    /**
     * @throws FilesystemException
     */
    public function move($oldPath, $newPath)
    {
        if( $this->filesystem->fileExists($this->helper->clearDblSlash($oldPath))){
            $this->filesystem->move($this->helper->clearDblSlash($oldPath), $this->helper->clearDblSlash($newPath));
        }
    }
}
