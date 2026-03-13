<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Service;

use Doctrine\ORM\EntityManagerInterface;

interface EntityManagerProviderInterface
{
    public function getEntityManager(): EntityManagerInterface;
}
