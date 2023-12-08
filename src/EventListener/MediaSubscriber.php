<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\EventListener;

use Adeliom\EasyMediaBundle\Entity\Media;
use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Adeliom\EasyMediaBundle\Types\EasyMediaType;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use League\Flysystem\FilesystemException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

#[AsDoctrineListener(Events::preUpdate)]
#[AsDoctrineListener(Events::loadClassMetadata)]
#[AsDoctrineListener(Events::postLoad)]
class MediaSubscriber
{
    private string $mediaClass;

    private array $fieldToHydrate = [];

    public function __construct(
        private EasyMediaManager $manager,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $parameterBag,
        private PropertyAccessor $propertyAccessor,
    ) {
        $this->mediaClass = $this->parameterBag->get('easy_media.media_entity');
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

    /**
     * @throws FilesystemException
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $classMetadata = $args->getClassMetadata();
        if ($classMetadata->getReflectionClass()->getName() === $this->mediaClass) {
            return;
        }
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            if ($classMetadata->getTypeOfField($fieldName) === EasyMediaType::EASYMEDIATYPE) {
                $this->fieldToHydrate[$fieldName] = $classMetadata->getReflectionClass()->getName();
            }
        }
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        $object = $args->getObject();
        dump($object);
        if ($object::class === $this->mediaClass) {
            return;
        }

        $fields = array_filter(
            $this->fieldToHydrate,
            static fn ($value, $key) => $value === $object::class,
            ARRAY_FILTER_USE_BOTH
        );

        foreach ($fields as $field => $class) {
            $value = $this->propertyAccessor->getValue($object, $field);
            if ($value === null) {
                continue;
            }
            $media = $this->entityManager->getRepository($this->mediaClass)->find($value);
            if ($media) {
                $this->propertyAccessor->setValue($object, $field, $media);
            }
        }
    }
}
