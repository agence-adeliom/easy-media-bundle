<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle;

use Adeliom\EasyMediaBundle\DependencyInjection\EasyMediaExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EasyMediaBundle extends Bundle
{
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
