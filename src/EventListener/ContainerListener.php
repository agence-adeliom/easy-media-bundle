<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerListener
{
    /**
     * @readonly
     */
    private \Symfony\Component\DependencyInjection\ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
