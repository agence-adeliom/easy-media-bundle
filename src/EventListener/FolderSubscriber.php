<?php

namespace Adeliom\EasyMediaBundle\EventListener;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class FolderSubscriber implements EventSubscriberInterface
{
    /** @var ContainerBagInterface */
    protected $parameterBag;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct($parameterBag)
    {
        $this->parameterBag = $parameterBag;

        // The internal adapter
        $adapter = new LocalFilesystemAdapter(
        // Determine the root directory
            $this->parameterBag->get("easy_media.storage"),
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
            ]),
            // Write flags
            LOCK_EX,
            // How to deal with links, either DISALLOW_LINKS or SKIP_LINKS
            // Disallowing them causes exceptions when encountered
            LocalFilesystemAdapter::DISALLOW_LINKS
        );
        $this->filesystem = new Filesystem($adapter);
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::preUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        /** @var Folder $folder */
        $folder = $args->getObject();
        if (!$folder instanceof Folder) {
            return;
        }
        $this->filesystem->createDirectory($folder->getPath());
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        /** @var Folder $folder */
        $folder = $args->getObject();
        if (!$folder instanceof Folder) {
            return;
        }
        $this->filesystem->deleteDirectory($folder->getPath());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        /** @var Folder $folder */
        $folder = $args->getObject();
        if (!$folder instanceof Folder) {
            return;
        }

        dump($args->getEntityChangeSet());

        if($args->hasChangedField("parent")){
            $oldPath = ($args->getOldValue("parent") ? $args->getOldValue("parent")->getPath() : "") . DIRECTORY_SEPARATOR . $folder->getSlug();
            $newPath = ($args->getNewValue("parent") ? $args->getNewValue("parent")->getPath() : "") . DIRECTORY_SEPARATOR . $folder->getSlug();
            $this->filesystem->move($oldPath, $newPath);
        }

        if($args->hasChangedField("slug")){
            $oldPath = basename($folder->getPath()) . DIRECTORY_SEPARATOR . $args->getOldValue("slug");
            $newPath = basename($folder->getPath()) . DIRECTORY_SEPARATOR . $args->getNewValue("slug");
            $this->filesystem->move($oldPath, $newPath);
        }
    }
}
