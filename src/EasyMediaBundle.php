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

        if ($this->container === null) {
            EasyMediaType::setMediaResolver(null);

            return;
        }

        $container = $this->container;
        EasyMediaType::setMediaResolver(static function (mixed $value) use ($container): mixed {
            $class = $container->getParameter('easy_media.media_entity');
            $repository = $container->get('doctrine.orm.entity_manager')->getRepository($class);

            return $repository->find($value);
        });
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new EasyMediaExtension();
        }

        return $this->extension;
    }
}
