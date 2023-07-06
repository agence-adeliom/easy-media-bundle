<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileUploaded extends Event
{
    /**
     * @var string
     */
    public const NAME = 'em.file.uploaded';

    private string $filePath;

    private string $mimeType;

    private array $options = [];

    public function __construct(string $filePath, string $mimeType, array $options = [])
    {
        $this->filePath = $filePath;
        $this->mimeType = $mimeType;
        $this->options = $options;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @return array<mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }


}
