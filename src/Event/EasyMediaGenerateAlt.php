<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

use Adeliom\EasyMediaBundle\Entity\Media;
use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaGenerateAlt extends Event
{
    /**
     * @var string
     */
    public const NAME = 'em.file.alt.generate';

    public Media $entity;

    public string $filePath;

    public string $alt;

    public function __construct($entity, $filePath, $alt = null)
    {
        $this->entity = $entity;
        $this->filePath = $filePath;
        $this->alt = $alt;
    }

    public function getEntity(): Media
    {
        return $this->entity;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getAlt(): string
    {
        return $this->alt;
    }

    public function setAlt(string $alt): void
    {
        $this->alt = $alt;
    }
}
