<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileDeleted extends Event
{
    public const NAME = 'em.file.deleted';
    public $filePath;
    public $isFolder;

    public function __construct($filePath, $isFolder)
    {
        $this->filePath = $filePath;
        $this->isFolder = $isFolder;
    }
}
