<?php
namespace Adeliom\EasyMediaBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EasyMediaFilesystem
{
    protected Filesystem $filesystem;

    public function __construct(protected string $rootPath)
    {
        // The internal adapter
        $adapter = new LocalFilesystemAdapter(
        // Determine the root directory
            $this->rootPath,
            // Customize how visibility is converted to unix permissions
            PortableVisibilityConverter::fromArray([
                'file' => [
                    'public' => 0644,
                    'private' => 0640,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0740,
                ],
            ], Visibility::PUBLIC),
            // Write flags
            LOCK_EX,
            // How to deal with links, either DISALLOW_LINKS or SKIP_LINKS
            // Disallowing them causes exceptions when encountered
            LocalFilesystemAdapter::DISALLOW_LINKS
        );
        $this->filesystem = new Filesystem($adapter);
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

}
