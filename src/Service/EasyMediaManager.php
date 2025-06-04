<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Service;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Event\EasyMediaBeforeFileCreated;
use Adeliom\EasyMediaBundle\Event\EasyMediaBeforeSetMetas;
use Adeliom\EasyMediaBundle\Exception\AlreadyExist;
use Adeliom\EasyMediaBundle\Exception\ExtNotAllowed;
use Adeliom\EasyMediaBundle\Exception\FolderAlreadyExist;
use Adeliom\EasyMediaBundle\Exception\FolderNotExist;
use Adeliom\EasyMediaBundle\Exception\NoFile;
use Adeliom\EasyMediaBundle\Exception\ProviderNotFound;
use Doctrine\ORM\EntityManagerInterface;
use Embed\Embed;
use Illuminate\Support\Str;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

class EasyMediaManager
{
    public function __construct(protected FilesystemOperator $filesystem,
                                protected EasyMediaHelper $helper,
                                public EntityManagerInterface $em,
                                protected ContainerBagInterface $parameters,
                                protected TranslatorInterface $translator,
                                protected EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getFilesystem(): FilesystemOperator
    {
        return $this->filesystem;
    }

    public function getHelper(): EasyMediaHelper
    {
        return $this->helper;
    }

    public function getPath(Media $media): ?string
    {
        return $this->getHelper()->getPath($media);
    }

    public function publicUrl(Media $media): array|string|null
    {
        if($mediaPath = $this->getPath($media)) {
            try {
                $publicUrl = $this->getFilesystem()->publicUrl($mediaPath);
                if (false !== strpos($this->helper->getBaseUrl(), '://')) {
                    $baseUrlPath = parse_url($this->helper->getBaseUrl(), PHP_URL_PATH) ?? "";
                    $baseUrl = '/';
                    if ($baseUrlPath) {
                        $baseUrl = str_replace($baseUrlPath, '', $this->helper->getBaseUrl());
                    }
                    $filePath = parse_url($publicUrl, PHP_URL_PATH);
                    $path = array_filter(explode('/', $baseUrlPath) + explode('/', $filePath));
                    $publicUrl = $this->helper->clearDblSlash(sprintf('%s/%s', $baseUrl, implode('/', $path)));
                }

                return $publicUrl;
            } catch (\Exception $exception) {
                return $this->helper->clearDblSlash(sprintf('%s/%s', $this->helper->getBaseUrl(), $mediaPath));
            }
        }

        return null;
    }

    public function downloadUrl(Media $media): array|string|null
    {
        return $this->publicUrl($media);
    }

    public function getFolder($id): ?Folder
    {
        return $this->getHelper()->getFolderRepository()->find($id);
    }

    public function getMedia(int|string|Media $media): ?Media
    {
        return $this->getHelper()->getMedia($media);
    }

    /**
     * @return Folder|false
     *
     * @throws FilesystemException
     */
    public function folderByPath(?string $path): Folder|null|false
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
     * @throws FilesystemException|FolderNotExist|FolderAlreadyExist
     */
    public function createFolder(?string $name, ?string $path = null): ?Folder
    {
        if ('.' === $path) {
            $path = '';
        }

        $class = $this->getHelper()->getFolderClassName();
        /** @var Folder $entity */
        $entity = new $class();

        if ($name) {
            $entity->setName($name);
        }

        $folder = $this->folderByPath($path);
        if (false === $folder && !empty($path)) {
            $folder = $this->createFolder(basename($path), dirname($path));
        }

        //if (null !== $folder && !empty($this->getHelper()->getFolderRepository()->findBy(['parent' => $folder, 'name' => $name]))) {
        //    throw new FolderAlreadyExist($this->translator->trans('error.already_exists', [], 'EasyMediaBundle'));
        //}

        $entity->setParent($folder ?: null);

        if (!$this->filesystem->directoryExists($entity->getPath())) {
            $this->filesystem->createDirectory($entity->getPath(), []);
        }

        $this->save($entity);

        return $entity;
    }

    /**
     * @throws AlreadyExist
     * @throws FolderAlreadyExist
     * @throws ContainerExceptionInterface
     * @throws ExtNotAllowed
     * @throws FilesystemException
     * @throws FolderNotExist
     * @throws NoFile
     * @throws NotFoundExceptionInterface
     * @throws ProviderNotFound
     */
    public function createMedia($source, ?string $path = null, ?string $name = null, ?Folder $folder = null): Media
    {
        $class = $this->getHelper()->getMediaClassName();
        /** @var Media $entity */
        $entity = new $class();

        if ($name) {
            $entity->setName($this->helper->cleanName($name));
        }

        if (! $folder) {
            $folder = $this->folderByPath($path);
            if (false === $folder) {
                $folder = $this->createFolder(basename($path), dirname($path));
            }
        }

        $entity->setFolder($folder ?: null);

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

    /**
     * @throws FilesystemException
     */
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
        if ($this->getFilesystem()->fileExists($this->helper->clearDblSlash($oldPath)) || $this->getFilesystem()->directoryExists($this->helper->clearDblSlash($oldPath))) {
            $this->getFilesystem()->move($this->helper->clearDblSlash($oldPath), $this->helper->clearDblSlash($newPath));
        }
    }

    private function createFromOembed(Media $entity, $source): Media
    {
        $embed = new Embed();
        $infos = $embed->get($source);

        if (($oembed = $infos->getOEmbed()) && !empty($infos->getOEmbed()->all())) {
            $name = $entity->getName() ?: $oembed->get('title');
            $entity->setName($name);
            $entity->setMime('application/json+oembed');
            $beforeSetMetasEvent = $this->eventDispatcher->dispatch(new EasyMediaBeforeSetMetas($entity, $source, [
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
                    'html' => $infos->code?->html,
                    'width' => $infos->code?->width,
                    'height' => $infos->code?->height,
                    'ratio' => $infos->code?->ratio,
                ],
            ]), EasyMediaBeforeSetMetas::NAME);
            $entity->setMetas($beforeSetMetasEvent->getMetas());
        } else {
            throw new ProviderNotFound($this->translator->trans('error.provider_not_found', [], 'EasyMediaBundle'));
        }

        return $entity;
    }

    /**
     * @throws AlreadyExist
     * @throws FilesystemException
     * @throws NoFile
     */
    private function createFromBase64(Media $entity, $source): Media
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

        if (!empty($this->getHelper()->getMediaRepository()->findBy(['folder' => $entity->getFolder(), 'name' => $entity->getName()]))) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'EasyMediaBundle'));
        }

        if (!$this->filesystem->fileExists($entity->getPath())) {
            $this->filesystem->write($entity->getPath(), $infos['data']);
        }

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
            $beforeSetMetasEvent = $this->eventDispatcher->dispatch(new EasyMediaBeforeSetMetas($entity, $source, [
                'dimensions' => [
                    'width' => $width,
                    'height' => $height,
                    'ratio' => $height / $width * 100,
                ],
            ]), EasyMediaBeforeSetMetas::NAME);
            $entity->setMetas($beforeSetMetasEvent->getMetas());
        }

        return $entity;
    }

    /**
     * @throws ExtNotAllowed
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws AlreadyExist
     * @throws FilesystemException
     * @throws NoFile
     */
    private function createFromImageURL(Media $entity, $source, $type): Media
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

        if (!empty($this->getHelper()->getMediaRepository()->findBy(['folder' => $entity->getFolder(), 'name' => $entity->getName()]))) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'EasyMediaBundle'));
        }

        try {
            if (!$this->filesystem->fileExists($entity->getPath())) {
                $stream = file_get_contents($source);
                $this->filesystem->write($entity->getPath(), $stream);
            }
        } catch (\Throwable) {
            throw new NoFile($this->translator->trans('error.cannot_write_file', [], 'EasyMediaBundle'));
        }

        [$width, $height] = getimagesize($source) ?: [0, 0];
        $beforeSetMetasEvent = $this->eventDispatcher->dispatch(new EasyMediaBeforeSetMetas($entity, $source, [
            'dimensions' => [
                'width' => $width,
                'height' => $height,
                'ratio' => $width > 0 ? $height / $width * 100 : 0,
            ],
        ]), EasyMediaBeforeSetMetas::NAME);
        $entity->setMetas($beforeSetMetasEvent->getMetas());

        try {
            $entity->setSize($this->filesystem->fileSize($entity->getPath()));
            $entity->setLastModified($this->filesystem->lastModified($entity->getPath()));
            $entity->setMime($this->filesystem->mimeType($entity->getPath()));
        } catch (\Throwable) {
            throw new NoFile($this->translator->trans('error.no_file', [], 'EasyMediaBundle'));
        }

        return $entity;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws AlreadyExist
     * @throws ContainerExceptionInterface
     * @throws FilesystemException
     * @throws NoFile
     * @throws ExtNotAllowed
     */
    private function createFromFile(Media $entity, $source): Media
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

        if (!empty($this->getHelper()->getMediaRepository()->findBy(['folder' => $entity->getFolder(), 'name' => $entity->getName()]))) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'EasyMediaBundle'));
        }

        if (@exif_imagetype($source->getPathname())) {
            [$width, $height] = getimagesize($source->getPathname());
            $beforeSetMetasEvent = $this->eventDispatcher->dispatch(new EasyMediaBeforeSetMetas($entity, $source, [
                'dimensions' => [
                    'width' => $width,
                    'height' => $height,
                    'ratio' => $height / $width * 100,
                ],
            ]), EasyMediaBeforeSetMetas::NAME);
            $entity->setMetas($beforeSetMetasEvent->getMetas());
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

                $beforeSetMetasEvent = $this->eventDispatcher->dispatch(new EasyMediaBeforeSetMetas($entity, $source, $datas), EasyMediaBeforeSetMetas::NAME);
                $entity->setMetas($beforeSetMetasEvent->getMetas());
            }
        } catch (\Exception) {
        }

        // check unexistence
        if (!$this->filesystem->fileExists($this->helper->clearDblSlash($entity->getPath()))) {
            // throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'EasyMediaBundle'));
            $stream = fopen($source->getRealPath(), 'rb+');
            $this->filesystem->writeStream($entity->getPath(), $stream);
            fclose($stream);
        }

        return $entity;
    }
}
