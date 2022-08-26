<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaFileRenamed extends Event
{
    /**
     * @var string
     */
    public const NAME = 'em.file.renamed';

    public $oldPath;

    public $newPath;

    public function __construct($oldPath, $newPath)
    {
    }
}
