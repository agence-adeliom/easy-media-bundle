<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Event;

use Adeliom\EasyMediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class EasyMediaGenerateAllAlt extends Event
{
    /**
     * @var string
     */
    public const NAME = 'em.file.alt.generate_all';

    public function __construct(private readonly Request $request)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
