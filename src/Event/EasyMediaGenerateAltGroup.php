<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaGenerateAltGroup extends Event
{
    /**
     * @var string
     */
    public const NAME = 'em.file.alt.generate_alt_group';

    /** @var array<int> $files */
    private array $files;

    public function __construct(array $files)
    {
        $this->files = $files;
    }

    public function getFiles(): array
    {
        return $this->files;
    }
}
