<?php

namespace Adeliom\EasyMediaBundle;

use Adeliom\EasyMediaBundle\DependencyInjection\EasyMediaExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EasyMediaBundle extends Bundle
{
    /**
     * Overridden to allow for the custom extension alias.
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new EasyMediaExtension();
        }
        return $this->extension;
    }
}
