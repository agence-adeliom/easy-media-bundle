<?php

namespace Adeliom\EasyMediaBundle\Imagine\Data;

use Imagine\Image\ImagineInterface;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Binary\Loader\LoaderInterface;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\Mime\MimeTypesInterface;

class EasyMediaDataLoader implements LoaderInterface
{
    public function __construct(
        private FilesystemAdapter $filesystem,
        protected MimeTypesInterface $extensionGuesser
    ) {
    }

    public function find($path): BinaryInterface|string
    {
        try {
            $mimeType = $this->filesystem->mimeType($path);
            if ($mimeType instanceof FileAttributes) {
                $mimeType = $mimeType->mimeType();
            }

            $extension = $this->getExtension($mimeType);

            return new Binary(
                $this->filesystem->read($path),
                $mimeType,
                $extension
            );
        } catch (FilesystemException $filesystemException) {
            throw new NotLoadableException(sprintf('Source image "%s" not found.', $path), 0, $filesystemException);
        }
    }

    private function getExtension(?string $mimeType): ?string
    {
        return $this->extensionGuesser->getExtensions($mimeType)[0] ?? null;
    }
}
