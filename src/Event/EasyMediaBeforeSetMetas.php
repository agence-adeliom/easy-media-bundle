<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

use Adeliom\EasyMediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaBeforeSetMetas extends Event
{
    /**
     * @var string
     */
    public const NAME = 'em.before.set.metas';

    public Media $entity;

    public null | string | File $source;

    /** @var array<mixed> */
    private array $metas;

    /** @param array<mixed> $metas */
    public function __construct(Media $entity, null | string | File $source, array $metas)
    {
        $this->entity = $entity;
        $this->source = $source;
        $this->metas = $metas;
    }

    public function getEntity(): Media
    {
        return $this->entity;
    }

    public function getSource(): null | string | File
    {
        return $this->source;
    }

    /** @return array<mixed> */
    public function getMetas(): array
    {
        return $this->metas;
    }

    /** @param array<mixed> $metas */
    public function setMetas(array $metas): void
    {
        $this->metas = $metas;
    }
}
