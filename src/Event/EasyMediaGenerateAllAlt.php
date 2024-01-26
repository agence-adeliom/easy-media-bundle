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

    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
