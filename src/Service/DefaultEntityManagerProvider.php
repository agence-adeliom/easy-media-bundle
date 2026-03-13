<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Service;

use Doctrine\ORM\EntityManagerInterface;

class DefaultEntityManagerProvider implements EntityManagerProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }
}
