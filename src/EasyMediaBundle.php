<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle;

use Adeliom\EasyMediaBundle\DependencyInjection\EasyMediaExtension;
use Adeliom\EasyMediaBundle\Types\EasyMediaType;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EasyMediaBundle extends Bundle
{
    public function boot(): void
    {
        parent::boot();

        // Initialize the EasyMediaType with the container
        if ($this->container !== null) {
            EasyMediaType::setContainer($this->container);
        }
    }

    /**
     * @return ExtensionInterface|null The container extension
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new EasyMediaExtension();
        }

        return $this->extension;
    }
}
