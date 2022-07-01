<?php

namespace Adeliom\EasyMediaBundle;

use Adeliom\EasyMediaBundle\DependencyInjection\EasyMediaExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EasyMediaBundle extends Bundle
{
    public function getContainerExtension(): ?EasyMediaExtension
    {
        if (null === $this->extension) {
            $this->extension = new EasyMediaExtension();
        }
        return $this->extension;
    }
}
