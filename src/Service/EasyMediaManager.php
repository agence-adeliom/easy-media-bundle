<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Service;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Exception\AlreadyExist;
use Adeliom\EasyMediaBundle\Exception\ExtNotAllowed;
use Adeliom\EasyMediaBundle\Exception\FolderNotExist;
use Adeliom\EasyMediaBundle\Exception\NoFile;
use Adeliom\EasyMediaBundle\Exception\ProviderNotFound;
use Doctrine\ORM\EntityManagerInterface;
use Embed\Embed;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCopyFile;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

class EasyMediaManager
{
    protected EntityManagerInterface $entityManager;

    protected FilesystemAdapter $adapter;

    public function __construct(protected EasyMediaFilesystem $filesystem, protected EasyMediaHelper $helper, public EntityManagerInterface $em, protected ContainerBagInterface $parameters, protected TranslatorInterface $translator)
    {
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function getHelper(): EasyMediaHelper
    {
        return $this->helper;
    }

    /**
     * @return mixed
     */
    public function getFolder($id)
    {
        return $this->getHelper()->getFolderRepository()->find($id);
    }

    /**
     * @return mixed
     */
    public function folderByPath(?string $path)
    {
        if (is_null($path) || $this->filesystem->directoryExists($path)) {
            $slugs = array_values(array_filter(explode('/', (string) $path)));
            $parent = null;
            foreach ($slugs as $i => $slug) {
                if (
                    ($folder = $this->getHelper()->getFolderRepository()->findOneBy([
                    'parent' => $parent,
                    'slug' => $slug,
                    ])) !== null
                ) {
                    $parent = $folder;
                }

                if ($i === count($slugs) - 1) {
                    return $folder ?: false;
                }
            }

            return $parent;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getMedia($id)
    {
        return $this->getHelper()->getMediaRepository()->find($id);
    }

    /**
     * @throws FilesystemException|FolderNotExist
     */
    public function createFolder(?string $name, $path = null): Folder
    {
        $class = $this->getHelper()->getFolderClassName();
        /** @var Folder $entity */
        $entity = new $class();

        if ($name) {
            $entity->setName($name);
        }

        $folder = $this->folderByPath($path);
        if (false === $folder) {
            throw new FolderNotExist('The folder does not exist');
        }

        if (!empty($this->getHelper()->getFolderRepository()->findBy(['parent' => $folder, 'name' => $name]))) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'EasyMediaBundle'));
        }

        $entity->setParent($folder);
        $this->filesystem->createDirectory($entity->getPath(), []);
        $this->save($entity);

        return $entity;
    }

    public function createMedia($source, $path = null, $name = null): Media
    {
        $class = $this->getHelper()->getMediaClassName();
        /** @var Media $entity */
        $entity = new $class();

        if ($name) {
            $entity->setName($this->helper->cleanName($name));
        }

        $folder = $this->folderByPath($path);
        if (false === $folder) {
            throw new FolderNotExist('The folder does not exist');
        }

        $entity->setFolder($folder);

        if (str_starts_with((string) $source, 'data:')) {
            $entity = $this->createFromBase64($entity, $source);
        } elseif (false !== filter_var($source, FILTER_VALIDATE_URL)) {
            if ($imageType = @exif_imagetype($source)) {
                $entity = $this->createFromImageURL($entity, $source, $imageType);
            } else {
                $entity = $this->createFromOembed($entity, $source);
            }
        } else {
            $entity = $this->createFromFile($entity, $source);
        }

        $this->save($entity);

        return $entity;
    }

    public function delete($item, $flush = true): void
    {
        $this->em->remove($item);

        if ($item instanceof Folder) {
            $this->filesystem->deleteDirectory($item->getPath());
        }

        if ($item instanceof Media) {
            $this->filesystem->delete($item->getPath());
        }

        if ($flush) {
            $this->em->flush();
        }
    }

    public function save($item, $flush = true): void
    {
        $this->em->persist($item);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * @throws FilesystemException
     */
    public function move($oldPath, $newPath): void
    {
        if ($this->filesystem->fileExists($this->helper->clearDblSlash($oldPath))) {
            $this->filesystem->move($this->helper->clearDblSlash($oldPath), $this->helper->clearDblSlash($newPath));
        }
    }

    private function createFromOembed(Media $entity, $source)
    {
        $embed = new Embed();
        $infos = $embed->get($source);

        if (($oembed = $infos->getOEmbed()) && !empty($infos->getOEmbed()->all())) {
            $name = $entity->getName() ?: $oembed->get('title');
            $entity->setName($name);
            $entity->setMime('application/json+oembed');
            $entity->setMetas([
                'provider' => [
                    'name' => $infos->providerName,
                    'url' => (string) $infos->providerUrl,
                ],
                'author' => [
                    'name' => $infos->authorName,
                    'url' => (string) $infos->authorUrl,
                ],
                'title' => (string) $infos->title,
                'url' => (string) $infos->url,
                'image' => (string) $infos->image,
                'icon' => (string) ($infos->icon ?: $infos->favicon),
                'type' => $oembed->get('type'),
                'code' => [
                    'html' => null !== $infos->code ? $infos->code->html : null,
                    'width' => null !== $infos->code ? $infos->code->width : null,
                    'height' => null !== $infos->code ? $infos->code->height : null,
                    'ratio' => null !== $infos->code ? $infos->code->ratio : null,
                ],
            ]);
        } else {
            throw new ProviderNotFound($this->translator->trans('error.provider_not_found', [], 'EasyMediaBundle'));
        }

        return $entity;
    }

    private function createFromBase64(Media $entity, $source)
    {
        if (preg_match('#^data\:([a-zA-Z]+\/[a-zA-Z]+);base64\,([a-zA-Z0-9\+\/]+\=*)$#', (string) $source, $matches)) {
            $infos = [
                'mime' => $matches[1],
                'data' => base64_decode((string) $matches[2]),
            ];
        } else {
            throw new NoFile($this->translator->trans('error.no_file', [], 'EasyMediaBundle'));
        }

        $entity->setName($this->helper->cleanName(''));
        $filename = strtolower((new AsciiSlugger())->slug(strtolower((string) $entity->getName()))->toString().'.'.EasyMediaHelper::mime2ext($infos['mime']));
        $entity->setSlug($filename);

        if ($this->filesystem->fileExists($entity->getPath())) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'EasyMediaBundle'));
        }

        $this->filesystem->write($entity->getPath(), $infos['data']);

        $entity->setSize($this->filesystem->fileSize($entity->getPath()));
        $entity->setLastModified($this->filesystem->lastModified($entity->getPath()));
        $entity->setMime($this->filesystem->mimeType($entity->getPath()));

        if (str_contains($entity->getMime(), 'image/')) {
            $tmp = tmpfile();
            if (false !== $tmp) {
                fwrite($tmp, $this->filesystem->read($entity->getPath()));
            }

            $path = stream_get_meta_data($tmp)['uri'];
            [$width, $height] = getimagesize($path);
            $entity->setMetas([
                'dimensions' => [
                    'width' => $width,
                    'height' => $height,
                    'ratio' => $height / $width * 100,
                ],
            ]);
        }

        return $entity;
    }

    private function createFromImageURL(Media $entity, $source, $type)
    {
        $urlPath = parse_url((string) $source, PHP_URL_PATH);
        $original = substr((string) $urlPath, strrpos($urlPath, '/') + 1);
        $name = $entity->getName() ?: pathinfo($original, PATHINFO_FILENAME);

        $file_type = image_type_to_mime_type($type);
        $ext_only = EasyMediaHelper::mime2ext($file_type) ?? pathinfo($original, PATHINFO_EXTENSION);

        $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower((string) $name))->toString().sprintf('.%s', $ext_only));
        $entity->setSlug($final_name_slug);

        if (empty($entity->getName())) {
            $entity->setName($name);
        }

        $ignore = array_merge($this->parameters->get('easy_media.unallowed_mimes'), ['application/octet-stream']);

        // check for mime type
        if (Str::contains($file_type, $ignore)) {
            throw new ExtNotAllowed($this->translator->trans('not_allowed_file_ext', [], 'EasyMediaBundle'));
        }

        // check existence
        if ($this->filesystem->fileExists($entity->getPath())) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'EasyMediaBundle'));
        }

        try {
            $stream = file_get_contents($source);
            $this->filesystem->write($entity->getPath(), $stream);

            [$width, $height] = getimagesize($source);
            $entity->setMetas([
                'dimensions' => [
                    'width' => $width,
                    'height' => $height,
                    'ratio' => $height / $width * 100,
                ],
            ]);

            $entity->setSize($this->filesystem->fileSize($entity->getPath()));
            $entity->setLastModified($this->filesystem->lastModified($entity->getPath()));
            $entity->setMime($this->filesystem->mimeType($entity->getPath()));
        } catch (\Throwable) {
            throw new NoFile($this->translator->trans('error.no_file', [], 'EasyMediaBundle'));
        }

        return $entity;
    }

    private function createFromFile(Media $entity, $source)
    {
        $datas = [];
        if (is_string($source)) {
            $source = new File($source);
        }

        if (!($source instanceof File)) {
            throw new NoFile();
        }

        if ($source instanceof UploadedFile) {
            $orig_name = $source->getClientOriginalName();
            $name = $entity->getName() ?: pathinfo($orig_name, PATHINFO_FILENAME);
            $ext_only = pathinfo($orig_name, PATHINFO_EXTENSION);
            if (($type = $source->getClientMimeType()) !== '' && ($type = $source->getClientMimeType()) !== '0') {
                $entity->setMime($type);
                if ($ext = EasyMediaHelper::mime2ext($type)) {
                    $ext_only = $ext;
                }
            }

            if (empty($entity->getName())) {
                $entity->setName($source->getClientOriginalName());
            }
        } else {
            $orig_name = $source->getFilename();
            $name = $entity->getName() ?: $source->getBasename('.'.$source->getExtension());
            $ext_only = pathinfo($orig_name, PATHINFO_EXTENSION);
            if ($type = $source->getMimeType()) {
                $entity->setMime($type);
                if ($ext = EasyMediaHelper::mime2ext($type)) {
                    $ext_only = $ext;
                }
            }

            if (empty($entity->getName())) {
                $entity->setName($source->getFilename());
            }
        }

        $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower((string) $name))->toString().sprintf('.%s', $ext_only));
        $entity->setSlug($final_name_slug);
        $entity->setSize($source->getSize());
        $entity->setLastModified($source->getMTime());

        // check for mime type
        if (Str::contains($entity->getMime(), $this->parameters->get('easy_media.unallowed_mimes'))) {
            throw new ExtNotAllowed($this->translator->trans('not_allowed_file_ext', [], 'EasyMediaBundle'));
        }

        // check for extension
        if (Str::contains($ext_only, $this->parameters->get('easy_media.unallowed_ext'))) {
            throw new ExtNotAllowed($this->translator->trans('not_allowed_file_ext', [], 'EasyMediaBundle'));
        }

        // check existence
        if ($this->filesystem->fileExists($this->helper->clearDblSlash($entity->getPath()))) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'EasyMediaBundle'));
        }

        if (@exif_imagetype($source->getPathname())) {
            [$width, $height] = getimagesize($source->getPathname());
            $entity->setMetas([
                'dimensions' => [
                    'width' => $width,
                    'height' => $height,
                    'ratio' => $height / $width * 100,
                ],
            ]);
        }

        try {
            if ($this->helper->fileIsType($entity->getMime(), 'video') || $this->helper->fileIsType($entity->getMime(), 'audio')) {
                $getID3 = new \getID3();
                $id3Datas = $getID3->analyze($source->getPathname());

                if (isset($id3Datas['video']) && $this->helper->fileIsType($entity->getMime(), 'video')) {
                    $datas = [
                        'duration' => $id3Datas['playtime_seconds'],
                        'frame_rate' => $id3Datas['video']['frame_rate'],
                        'dimensions' => [
                            'width' => $id3Datas['video']['resolution_x'],
                            'height' => $id3Datas['video']['resolution_y'],
                            'ratio' => $id3Datas['video']['resolution_y'] / $id3Datas['video']['resolution_x'] * 100,
                        ],
                    ];
                }

                if (isset($id3Datas['audio']) && $this->helper->fileIsType($entity->getMime(), 'audio')) {
                    $datas = [
                        'duration' => $id3Datas['playtime_seconds'],
                        'tags' => [],
                    ];
                    if (!empty($id3Datas['id3v1']['title'])) {
                        $datas['tags']['title'] = $id3Datas['id3v1']['title'];
                    }

                    if (!empty($id3Datas['id3v1']['artist'])) {
                        $datas['tags']['artist'] = $id3Datas['id3v1']['artist'];
                    }

                    if (!empty($id3Datas['id3v1']['album'])) {
                        $datas['tags']['album'] = $id3Datas['id3v1']['album'];
                    }

                    if (!empty($id3Datas['id3v1']['year'])) {
                        $datas['tags']['year'] = $id3Datas['id3v1']['year'];
                    }
                }

                $entity->setMetas($datas);
            }
        } catch (\Exception $exception) {
        }

        try {
            $stream = fopen($source->getRealPath(), 'r+');
            $this->filesystem->writeStream($entity->getPath(), $stream);
            fclose($stream);
        } catch (FileException|UnableToCopyFile $exception) {
            dump($exception);
        }

        return $entity;
    }
}
