<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\EventListener;

use Adeliom\EasyMediaBundle\Types\EasyMediaType;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerListener
{
    public function __construct(private readonly ContainerInterface $container)
    {
        EasyMediaType::setContainer($this->container);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
