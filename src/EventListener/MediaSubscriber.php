<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\EventListener;

use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use League\Flysystem\FilesystemException;

#[AsDoctrineListener(Events::preUpdate)]
class MediaSubscriber
{
    public function __construct(private EasyMediaManager $manager)
    {
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
