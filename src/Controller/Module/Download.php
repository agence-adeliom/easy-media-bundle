<?php
namespace Adeliom\EasyMediaBundle\Controller\Module;


use League\Flysystem\StorageAttributes;
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
     * @throws \League\Flysystem\FilesystemException
     */
    public function downloadFolder(Request $request)
    {
        $name = $request->request->get("name");
        $folders = $request->request->get("folders");

        /** @var string[] $allPaths */
        $allPaths = $this->filesystem->listContents(sprintf("%s/%s" , $folders, $name))
            ->filter(fn (StorageAttributes $attributes) => $attributes->isFile())
            ->map(fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();

        if ($allPaths){
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
    public function downloadFiles(Request $request)
    {
        $list = json_decode($request->request->get("list", []), true);
        $name = $request->request->get("name");

        return $this->zipAndDownload(
            $name . '-files',
            $list
        );
    }

    /**
     * zip ops.
     *
     * @param mixed $name
     * @param mixed $list
     * @return StreamedResponse
     */
    protected function zipAndDownload($name, $list)
    {
        return new StreamedResponse(function () use ($name, $list): void {
            // track changes
            $counter  = 100 / count($list);
            $progress = 0;

            $zip = new ZipStream("$name.zip", $this->getZipOptions());

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
            $counter  = 100 / count($list);
            $progress = 0;

            $zip = new ZipStream("$name.zip", $this->getZipOptions());

            foreach ($list as $file) {
                $dir_name   = pathinfo($file, PATHINFO_DIRNAME);
                $file_name  = pathinfo($file, PATHINFO_BASENAME);
                $full_name  = "$dir_name/$file_name";
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

}
