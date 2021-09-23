<?php

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileUploaded extends Event
{
    public const NAME = 'em.file.uploaded';

    /**
     * @var string
     */
    public $filePath;

    /**
     * @var string
     */
    public $mimeType;

    /**
     * @var array
     */
    public $options = [];


    public function __construct($filePath, $mimeType, $options = [])
    {
        $this->filePath = $filePath;
        $this->mimeType = $mimeType;
        $this->options = $options;
    }
}
