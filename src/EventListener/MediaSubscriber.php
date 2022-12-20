<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\EventListener;

use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class MediaSubscriber implements EventSubscriberInterface
{
    public function __construct(protected readonly EasyMediaManager $manager)
    {
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
        ];
    }

    /**
     * @throws FilesystemException
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        /** @var Media $media */
        $media = $args->getObject();
        if (!$media instanceof Media) {
            return;
        }

        if ($args->hasChangedField('folder')) {
            $oldPath = ($args->getOldValue('folder') ? $args->getOldValue('folder')->getPath() : '').DIRECTORY_SEPARATOR.$media->getSlug();
            $newPath = ($args->getNewValue('folder') ? $args->getNewValue('folder')->getPath() : '').DIRECTORY_SEPARATOR.$media->getSlug();
            $this->manager->move($oldPath, $newPath);
        }
    }
}
