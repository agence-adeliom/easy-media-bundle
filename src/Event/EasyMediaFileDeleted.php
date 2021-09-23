<?php

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileDeleted extends Event
{
    public const NAME = 'em.file.deleted';

    /**
     * @var string
     */
    public $filePath;

    /**
     * @var bool
     */
    public $isFolder;


    public function __construct($filePath, $isFolder)
    {
        $this->filePath = $filePath;
        $this->isFolder = $isFolder;
    }
}
