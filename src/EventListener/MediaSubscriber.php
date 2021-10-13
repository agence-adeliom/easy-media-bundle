<?php

namespace Adeliom\EasyMediaBundle\EventListener;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class MediaSubscriber implements EventSubscriberInterface
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
        /** @var Media $media */
        $media = $args->getObject();
        if (!$media instanceof Media) {
            return;
        }
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        /** @var Media $media */
        $media = $args->getObject();
        if (!$media instanceof Media) {
            return;
        }
        $this->filesystem->delete($media->getPath());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        /** @var Media $media */
        $media = $args->getObject();
        if (!$media instanceof Media) {
            return;
        }
    }
}
