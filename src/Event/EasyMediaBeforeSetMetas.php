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

    /** @param array<mixed> $metas */
    public function __construct(public Media $entity, public null | string | File $source, private array $metas)
    {
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
