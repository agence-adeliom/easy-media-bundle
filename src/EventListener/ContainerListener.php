<?php

namespace Adeliom\EasyMediaBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerListener
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
