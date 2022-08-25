<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

class EasyMediaFileSaved extends EasyMediaFileUploaded
{
    /**
     * @var string
     */
    public const NAME = 'em.file.saved';
}
