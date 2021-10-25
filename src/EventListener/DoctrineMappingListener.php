<?php

namespace Adeliom\EasyMediaBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This class adds automatically the ManyToOne and OneToMany relations in Page and Category entities,
 * because it's normally impossible to do so in a mapped superclass.
 */
class DoctrineMappingListener implements EventSubscriber
{
    /**
     * @var string
     */
    private $mediaClass;

    /**
     * @var string
     */
    private $folderClass;

    public function __construct(string $mediaClass, string $folderClass)
    {
        $this->mediaClass = $mediaClass;
        $this->folderClass = $folderClass;
    }

    public function getSubscribedEvents()
    {
        return [Events::loadClassMetadata];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata = $eventArgs->getClassMetadata();

        $isFolder     = is_a($classMetadata->getName(), $this->folderClass, true);
        $isMedia     = is_a($classMetadata->getName(), $this->mediaClass, true);
        if ($isFolder) {
            $this->processParent($classMetadata, $this->folderClass);
            $this->processChildren($classMetadata, $this->folderClass);
            $this->processMedias($classMetadata, $this->mediaClass);
        }

        if ($isMedia) {
            $this->processFolder($classMetadata, $this->folderClass);
        }

    }

    /**
     * Declare self-bidirectionnal mapping for parent.
     */
    private function processParent(ClassMetadata $classMetadata, string $class): void
    {
        if (!$classMetadata->hasAssociation('parent')) {
            $classMetadata->mapManyToOne([
                'fieldName' => 'parent',
                'targetEntity' => $class,
                'inversedBy' => 'children',
                'cascade' => ["persist","detach"],
                "joinColumns" => [
                    [
                        "name" => "parent_id",
                        "referencedColumnName" => "id",
                        "nullable" => "true",
                        "onDelete" => "SET NULL",
                    ]
                ]
            ]);
        }
    }

    /**
     * Declare self-bidirectionnal mapping for children
     */
    private function processChildren(ClassMetadata $classMetadata, string $class): void
    {
        if (!$classMetadata->hasAssociation('children')) {
            $classMetadata->mapOneToMany([
                'fieldName' => 'children',
                'targetEntity' => $class,
                'mappedBy' => 'parent',
                'cascade' => ["persist", "remove"]
            ]);
        }
    }

    private function processMedias(ClassMetadata $classMetadata, string $class): void
    {
        if (!$classMetadata->hasAssociation('medias')) {
            $classMetadata->mapOneToMany([
                'fieldName' => 'medias',
                'targetEntity' => $class,
                'mappedBy' => 'folder',
                'cascade' => ["persist", "remove"]
            ]);
        }
    }

    private function processFolder(ClassMetadata $classMetadata, string $class): void
    {
        if (!$classMetadata->hasAssociation('folder')) {
            $classMetadata->mapManyToOne([
                'fieldName' => 'folder',
                'targetEntity' => $class,
                'inversedBy' => 'medias',
                'cascade' => ["persist"]
            ]);
        }
    }
}
