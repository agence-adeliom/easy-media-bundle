<?php

namespace Adeliom\EasyMediaBundle\EventListener;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
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
    /**
     * @var EasyMediaManager
     */
    protected $manager;

    public function __construct(EasyMediaManager $manager)
    {
        $this->manager = $manager;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
        ];
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        /** @var Media $media */
        $media = $args->getObject();
        if (!$media instanceof Media) {
            return;
        }

        if($args->hasChangedField("folder")){
            $oldPath = ($args->getOldValue("folder") ? $args->getOldValue("folder")->getPath() : "") . DIRECTORY_SEPARATOR . $media->getSlug();
            $newPath = ($args->getNewValue("folder") ? $args->getNewValue("folder")->getPath() : "") . DIRECTORY_SEPARATOR . $media->getSlug();
            $this->manager->move($oldPath, $newPath);
        }
    }
}
