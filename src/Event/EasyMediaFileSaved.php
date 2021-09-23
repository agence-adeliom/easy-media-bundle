<?php

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileSaved extends EasyMediaFileUploaded
{
    public const NAME = 'em.file.saved';
}
