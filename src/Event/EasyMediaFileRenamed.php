<?php

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileRenamed extends Event
{
    public const NAME = 'em.file.renamed';

    public $oldPath;
    public $newPath;

    public function __construct($oldPath, $newPath)
    {
        $this->oldPath = $oldPath;
        $this->newPath = $newPath;
    }
}
