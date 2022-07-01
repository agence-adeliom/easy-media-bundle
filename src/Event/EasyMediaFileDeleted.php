<?php

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileDeleted extends Event
{
    public const NAME = 'em.file.deleted';

    public function __construct(public string $filePath, public bool $isFolder)
    {
    }
}
