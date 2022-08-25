<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

class EasyMediaFileMoved extends EasyMediaFileRenamed
{
    /**
     * @var string
     */
    public const NAME = 'em.file.moved';
}
