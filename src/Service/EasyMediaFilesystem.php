<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;

class EasyMediaFilesystem extends Filesystem
{
    public FilesystemAdapter $adapter;

    public function __construct(FilesystemAdapter $adapter, array $config = [], PathNormalizer $pathNormalizer = null)
    {
        parent::__construct($adapter, $config, $pathNormalizer);
    }

    public function getFilesystem(): Filesystem
    {
        return $this;
    }
}
