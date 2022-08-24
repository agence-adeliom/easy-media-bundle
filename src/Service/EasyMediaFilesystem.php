<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\FlysystemBundle\Lazy\LazyFactory;

class EasyMediaFilesystem extends Filesystem
{
    /**
     * @var FilesystemAdapter
     */
    public FilesystemAdapter $adapter;

    public function __construct(FilesystemAdapter $adapter, array $config = [], PathNormalizer $pathNormalizer = null)
    {
        parent::__construct($adapter, $config, $pathNormalizer);
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this;
    }
}
