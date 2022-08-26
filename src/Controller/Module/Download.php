<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Controller\Module;

use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

trait Download
{
    /**
     * zip folder.
     *
     * @param Request $request [description]
     *
     * @return StreamedResponse [type] [description]
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function downloadFolder(Request $request): StreamedResponse
    {
        $name = $request->request->get('name');
        $folders = $request->request->get('folders');

        /** @var array<string> $allPaths */
        $allPaths = $this->filesystem->listContents(sprintf('%s/%s', $folders, $name))
            ->filter(static fn (StorageAttributes $attributes) => $attributes->isFile())
            ->map(static fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();

        if ([] !== $allPaths) {
            return $this->zipAndDownloadDir(
                $name,
                $allPaths
            );
        }

        exit;
    }

    /**
     * zip files.
     *
     * @param Request $request [description]
     *
     * @return StreamedResponse [type] [description]
     */
    public function downloadFiles(Request $request): StreamedResponse
    {
        $list = json_decode($request->request->get('list', []), true, 512, JSON_THROW_ON_ERROR);
        $name = $request->request->get('name');

        return $this->zipAndDownload(
            $name.'-files',
            $list
        );
    }

    /**
     * zip ops.
     */
    protected function zipAndDownload(mixed $name, mixed $list): StreamedResponse
    {
        return new StreamedResponse(function () use ($name, $list): void {
            // track changes
            $counter = 100 / count($list);
            $progress = 0;

            $zip = new ZipStream(sprintf('%s.zip', $name), $this->getZipOptions());

            foreach ($list as $file) {
                $name = $file['name'];
                $path = $file['storage_path'];
                $streamRead = $this->filesystem->readStream($path);

                // add to zip
                if ($streamRead) {
                    $progress += $counter;

                    $zip->addFileFromStream($name, $streamRead);
                }
            }

            $zip->finish();
        });
    }

    protected function zipAndDownloadDir($name, $list)
    {
        return new StreamedResponse(function () use ($name, $list): void {
            // track changes
            $counter = 100 / count($list);
            $progress = 0;

            $zip = new ZipStream(sprintf('%s.zip', $name), $this->getZipOptions());

            foreach ($list as $file) {
                $dir_name = pathinfo($file, PATHINFO_DIRNAME);
                $file_name = pathinfo($file, PATHINFO_BASENAME);
                $full_name = sprintf('%s/%s', $dir_name, $file_name);
                $streamRead = $this->filesystem->readStream($file);

                // add to zip
                if ($streamRead) {
                    $progress += $counter;

                    $zip->addFileFromStream($full_name, $streamRead);
                }
            }

            $zip->finish();
        });
    }

    protected function getZipOptions()
    {
        $options = new Archive();
        // $options->setZeroHeader(true);
        // $options->setEnableZip64(false)
        $options->setContentType('application/octet-stream');
        $options->setSendHttpHeaders(true);
        $options->setHttpHeaderCallback('header');
        $options->setDeflateLevel(9);

        return $options;
    }

    public function downloadFile(string $path)
    {
        try {
            $mimeType = $this->filesystem->mimeType($path);

            $stream = $this->filesystem->readStream($path);
            $response = new StreamedResponse(static function () use ($stream) {
                fpassthru($stream);
                exit;
            });

            $response->setLastModified((new \DateTime())->setTimestamp($this->filesystem->lastModified($path)));
            $response->headers->set('Content-Type', $mimeType);
            $response->setPublic();
            $response->setMaxAge(60 * 12);
            $response->setSharedMaxAge(60 * 12);

            return $response;
        } catch (FilesystemException $filesystemException) {
            throw new NotLoadableException(sprintf('Source image "%s" not found.', $path), 0, $filesystemException);
        }
    }
}
