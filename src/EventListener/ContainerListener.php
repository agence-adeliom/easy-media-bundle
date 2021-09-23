<?php

namespace Adeliom\EasyMediaBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

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
