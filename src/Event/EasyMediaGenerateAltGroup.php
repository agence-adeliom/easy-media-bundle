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

    public function __construct(
        /** @var array<int> $files */
        private readonly array $files
    )
    {
    }

    public function getFiles(): array
    {
        return $this->files;
    }
}
