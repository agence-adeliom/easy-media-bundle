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

    public function __construct(public Media $entity, public string $filePath, public ?string $alt = null)
    {
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
