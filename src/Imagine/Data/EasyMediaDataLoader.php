<?php

namespace Adeliom\EasyMediaBundle\Imagine\Data;

use Imagine\Image\ImagineInterface;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Liip\ImagineBundle\Binary\Loader\LoaderInterface;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface as DeprecatedExtensionGuesserInterface;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\Mime\MimeTypesInterface;

class EasyMediaDataLoader implements LoaderInterface
{
    private FilesystemAdapter $filesystem;
    protected MimeTypesInterface $extensionGuesser;

    public function __construct(FilesystemAdapter $filesystem, MimeTypesInterface $extensionGuesser)
    {
        $this->extensionGuesser = $extensionGuesser;
        $this->filesystem = $filesystem;
    }

    public function find($path)
    {
        try {
            $mimeType = $this->filesystem->mimeType($path);
            if($mimeType instanceof FileAttributes){
                $mimeType = $mimeType->mimeType();
            }
            $extension = $this->getExtension($mimeType);

            return new Binary(
                $this->filesystem->read($path),
                $mimeType,
                $extension
            );
        } catch (FilesystemException $exception) {
            throw new NotLoadableException(sprintf('Source image "%s" not found.', $path), 0, $exception);
        }
    }

    private function getExtension(?string $mimeType): ?string
    {
        return $this->extensionGuesser->getExtensions($mimeType)[0] ?? null;
    }
}
