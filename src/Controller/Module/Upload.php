<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use Adeliom\EasyMediaBundle\Event\EasyMediaBeforeFileCreated;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileSaved;
use Adeliom\EasyMediaBundle\Event\EasyMediaFileUploaded;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\File;
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
        $upload_folder_id = $request->request->get('upload_folder');
        $folder = null;
        if (!empty($upload_folder_id)) {
            $folder = $this->manager->getFolder($upload_folder_id);
        }

        $random_name = filter_var($request->request->get('random_names'), FILTER_VALIDATE_BOOLEAN);
        $custom_attr = json_decode($request->request->get('custom_attrs', '[]'), true, 512, JSON_THROW_ON_ERROR);
        $result = [];

        if (($one = $request->files->get('file')) && $this->allowUpload($one)) {
            try {
                $one = $this->optimizeUpload($one);
                $orig_name = $one->getClientOriginalName();
                $name = $random_name ? $this->helper->getRandomString() : null;

                if ($request->request->get('dzuuid')) {
                    $chunksRes = self::resumableUpload($request, $one->getRealPath(), $orig_name, $this->chunksDir);

                    if (!$chunksRes['final']) {
                        return new JsonResponse($chunksRes);
                    }

                    $one = new File($chunksRes['path']);
                }

                if (!empty($custom_attr)) {
                    $custom_attr = array_filter($custom_attr, static fn ($entry) => $entry['name'] === $orig_name);
                    $custom_attr = current($custom_attr);
                }

                $file_options = empty($custom_attr) ? [] : $custom_attr['options'];
                $beforeFileCreatedEvent = $this->eventDispatcher->dispatch(new EasyMediaBeforeFileCreated($one, $folder ? $folder->getPath() : null, $name), EasyMediaBeforeFileCreated::NAME);
                $one = $beforeFileCreatedEvent->getData();
                $folderPath = $beforeFileCreatedEvent->getFolderPath();
                $name = $beforeFileCreatedEvent->getName();
                $media = $this->manager->createMedia($one, $folderPath, $name);
                if ($one instanceof File) {
                    $filesystem = new Filesystem();
                    $filesystem->remove(Path::normalize($one->getRealPath()));
                }

                $media->setMetas(array_merge($media->getMetas(), $file_options));
                $this->manager->save($media);
                $this->eventDispatcher->dispatch(new EasyMediaFileUploaded($media->getPath(), $media->getMime(), $media->getMetas()), EasyMediaFileUploaded::NAME);
                $result[] = [
                    'success' => true,
                    'file_name' => $media->getName(),
                ];
            } catch (\Exception $exception) {
                $result[] = [
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }
        } else {
            $result[] = [
                'success' => false,
                'message' => $this->translator->trans('error.cant_upload', [], 'EasyMediaBundle'),
            ];
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
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $upload_folder_id = $data['folder'];
            $folder = null;
            if (!empty($upload_folder_id)) {
                $folder = $this->manager->getFolder($upload_folder_id);
            }

            $upload_path = $folder ? $folder->getPath() : null;
            $original = $data['name'];
            $name_only = pathinfo((string) $original, PATHINFO_FILENAME).'_'.$this->helper->getRandomString();

            try {
                $beforeFileCreatedEvent = $this->eventDispatcher->dispatch(new EasyMediaBeforeFileCreated($data['data'], $upload_path, $name_only), EasyMediaBeforeFileCreated::NAME);
                $data['data'] = $beforeFileCreatedEvent->getData();
                $upload_path = $beforeFileCreatedEvent->getFolderPath();
                $name_only = $beforeFileCreatedEvent->getName();
                $media = $this->manager->createMedia($data['data'], $upload_path, $name_only);
                $this->eventDispatcher->dispatch(new EasyMediaFileSaved($media->getPath(), $media->getMime()), EasyMediaFileSaved::NAME);
                $result = [
                    'success' => true,
                    'message' => $media->getName(),
                ];
            } catch (\Exception $exception) {
                $result = [
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => $this->translator->trans('error.cant_upload', [], 'EasyMediaBundle'),
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
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $url = $data['url'];
            $upload_folder_id = $data['folder'];
            $folder = null;
            if (!empty($upload_folder_id)) {
                $folder = $this->manager->getFolder($upload_folder_id);
            }

            try {
                $random_name = filter_var($data['random_names'], FILTER_VALIDATE_BOOLEAN);
                $name = $random_name ? $this->helper->getRandomString() : null;

                $beforeFileCreatedEvent = $this->eventDispatcher->dispatch(new EasyMediaBeforeFileCreated($url, $folder ? $folder->getPath() : null, $name), EasyMediaBeforeFileCreated::NAME);
                $url = $beforeFileCreatedEvent->getData();
                $folderPath = $beforeFileCreatedEvent->getFolderPath();
                $name = $beforeFileCreatedEvent->getName();
                $media = $this->manager->createMedia($url, $folderPath, $name);
                $this->eventDispatcher->dispatch(new EasyMediaFileSaved($media->getPath(), $media->getMime()), EasyMediaFileSaved::NAME);

                $result = [
                    'success' => true,
                    'message' => $media->getName(),
                ];
            } catch (\Exception $exception) {
                $result = [
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => $this->translator->trans('error.cant_upload', [], 'EasyMediaBundle'),
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * allow/disallow user upload.
     *
     * @param null $file
     *
     * @return bool [boolean]
     */
    protected function allowUpload($file = null): bool
    {
        return true;
    }

    /**
     * do something to file b4 its saved to the server.
     *
     * @return UploadedFile $file
     */
    protected function optimizeUpload(UploadedFile $file): UploadedFile
    {
        return $file;
    }

    private static function resumableUpload(Request $request, string $tmpFilePath, string $filename, string $chunksDir)
    {
        $successes = [];
        $errors = [];
        $warnings = [];

        $identifier = trim($request->get('dzuuid', ''));
        $fileChunksFolder = sprintf('%s/%s', $chunksDir, $identifier);
        $filesystem = new Filesystem();
        $filesystem->mkdir(Path::normalize($fileChunksFolder));

        $filename = str_replace([' ', '(', ')'], '_', $filename); // remove problematic symbols
        $info = pathinfo($filename);
        $extension = isset($info['extension']) ? '.'.strtolower($info['extension']) : '';
        $filename = $info['filename'];

        $totalSize = (int) $request->get('dztotalfilesize', 0);
        $totalChunks = (int) $request->get('dztotalchunkcount', 0);
        $chunkInd = (int) $request->get('dzchunkindex', 0);
        $chunkSize = (int) $request->get('dzchunksize', 0);
        $startByte = (int) $request->get('dzchunkbyteoffset', 0);

        $chunkFile = sprintf('%s/%s.part%d', $fileChunksFolder, $filename, $chunkInd);

        if (!move_uploaded_file($tmpFilePath, $chunkFile)) {
            $errors[] = ['text' => 'Move error', 'name' => $filename, 'index' => $chunkInd];
        }

        if (0 === count($errors) && $newPath = self::checkAllParts($fileChunksFolder, $filename, $extension, $totalSize, $totalChunks, $chunksDir, $successes, $errors, $warnings)) {
            return ['final' => true, 'path' => $newPath, 'successes' => $successes, 'errors' => $errors, 'warnings' => $warnings];
        }

        return ['final' => false, 'successes' => $successes, 'errors' => $errors, 'warnings' => $warnings];
    }

    private static function checkAllParts(string $fileChunksFolder, string $filename, string $extension, int $totalSize, int $totalChunks, string $chunksDir, array &$successes, array &$errors, array &$warnings)
    {
        $parts = glob(Path::normalize(sprintf('%s/*', $fileChunksFolder)));
        $successes[] = count($parts).sprintf(' of %d parts done so far in %s', $totalChunks, $fileChunksFolder);
        $filesystem = new Filesystem();

        // check if all the parts present, and create the final destination file
        if (count($parts) === (int) $totalChunks) {
            $loaded_size = 0;
            foreach ($parts as $file) {
                $loaded_size += filesize($file);
            }

            if (
                $loaded_size >= $totalSize && [] === $errors && $newPath = self::createFileFromChunks(
                    $fileChunksFolder,
                    $filename,
                    $extension,
                    $totalSize,
                    $totalChunks,
                    $chunksDir,
                    $successes,
                    $errors,
                    $warnings
                )
            ) {
                $filesystem->remove(Path::normalize($fileChunksFolder));

                return $newPath;
            }
        }

        return false;
    }

    private static function createFileFromChunks(string $fileChunksFolder, string $fileName, string $extension, int $totalSize, int $totalChunks, string $chunksDir, array &$successes, array &$errors, array &$warnings)
    {
        $relPath = Path::normalize($chunksDir.'/assembled');
        $filesystem = new Filesystem();
        $filesystem->mkdir($relPath);

        $saveName = self::getNextAvailableFilename($relPath, $fileName, $extension, $errors);

        if (!$saveName) {
            return false;
        }

        $fp = fopen(sprintf('%s/%s%s', $relPath, $saveName, $extension), 'w');
        if (false === $fp) {
            $errors[] = 'cannot create the destination file';

            return false;
        }

        for ($i = 0; $i < $totalChunks; ++$i) {
            fwrite($fp, file_get_contents(Path::normalize($fileChunksFolder.'/'.$fileName.'.part'.$i)));
        }

        fclose($fp);

        return Path::normalize(sprintf('%s/%s%s', $relPath, $saveName, $extension));
    }

    private static function getNextAvailableFilename(string $relPath, string $origFileName, string $extension, array &$errors)
    {
        if (file_exists(Path::normalize(sprintf('%s/%s%s', $relPath, $origFileName, $extension)))) {
            $i = 0;
            while (file_exists(Path::normalize(sprintf('%s/%s_', $relPath, $origFileName).(++$i).$extension)) && $i < 10000) {
            }

            if ($i >= 10000) {
                $errors[] = sprintf('Can not create unique name for saving file %s%s', $origFileName, $extension);

                return false;
            }

            return $origFileName.'_'.$i;
        }

        return $origFileName;
    }
}
