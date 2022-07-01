<?php

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileUploaded extends Event
{
    public const NAME = 'em.file.uploaded';

    public function __construct(public string $filePath, public string $mimeType, public array $options = [])
    {
    }
}
