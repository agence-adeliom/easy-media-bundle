<?php

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileRenamed extends Event
{
    public const NAME = 'em.file.renamed';

    public function __construct(public string $oldPath, public string $newPath)
    {
    }
}
