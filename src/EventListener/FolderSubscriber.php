<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\EventListener;

use Adeliom\EasyMediaBundle\Entity\Folder;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use League\Flysystem\FilesystemException;

#[AsDoctrineListener(Events::preUpdate)]
class FolderSubscriber
{
    public function __construct(private EasyMediaManager $manager)
    {
    }

    /**
     * @throws FilesystemException
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        /** @var Folder $folder */
        $folder = $args->getObject();
        if (!$folder instanceof Folder) {
            return;
        }

        if ($args->hasChangedField('parent')) {
            $oldPath = ($args->getOldValue('parent') ? $args->getOldValue('parent')->getPath() : '').DIRECTORY_SEPARATOR.$folder->getSlug();
            $newPath = ($args->getNewValue('parent') ? $args->getNewValue('parent')->getPath() : '').DIRECTORY_SEPARATOR.$folder->getSlug();
            $this->manager->move($oldPath, $newPath);
        }

        if ($args->hasChangedField('slug')) {
            $oldPath = basename($folder->getPath()).DIRECTORY_SEPARATOR.$args->getOldValue('slug');
            $newPath = basename($folder->getPath()).DIRECTORY_SEPARATOR.$args->getNewValue('slug');
            $this->manager->move($oldPath, $newPath);
        }
    }
}
