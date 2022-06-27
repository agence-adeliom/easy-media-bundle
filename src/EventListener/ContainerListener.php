<?php

namespace Adeliom\EasyMediaBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerListener
{
    private \Symfony\Component\DependencyInjection\ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}
