<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaBeforeFileCreated extends Event
{
    /**
     * @var string
     */
    public const NAME = 'em.before.file.created';

    public function __construct(private File | string $data, private ?string $folderPath = null, private ?string $name = null)
    {
    }

    public function getData(): string|File
    {
        return $this->data;
    }

    public function getFolderPath(): ?string
    {
        return $this->folderPath;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setData(File|string $data): void
    {
        $this->data = $data;
    }

    public function setFolderPath(?string $folderPath): void
    {
        $this->folderPath = $folderPath;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

}
