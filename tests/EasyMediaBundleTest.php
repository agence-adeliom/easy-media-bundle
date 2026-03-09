<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Tests;

use Adeliom\EasyMediaBundle\DependencyInjection\EasyMediaExtension;
use Adeliom\EasyMediaBundle\EasyMediaBundle;
use Adeliom\EasyMediaBundle\Tests\Fixtures\Entity\TestMedia;
use Adeliom\EasyMediaBundle\Types\EasyMediaType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(\Adeliom\EasyMediaBundle\EasyMediaBundle::class)]
final class EasyMediaBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        EasyMediaType::setMediaResolver(null);
    }

    public function testBundleCreatesItsContainerExtension(): void
    {
        $extension = (new EasyMediaBundle())->getContainerExtension();

        self::assertInstanceOf(EasyMediaExtension::class, $extension);
        self::assertSame('easy_media', $extension?->getAlias());
    }

    public function testBootRegistersDoctrineBackedMediaResolver(): void
    {
        $media = new TestMedia();
        $property = new \ReflectionProperty($media, 'id');
        $property->setValue($media, 33);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(33)
            ->willReturn($media);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(TestMedia::class)
            ->willReturn($repository);

        $container = new ContainerBuilder();
        $container->setParameter('easy_media.media_entity', TestMedia::class);
        $container->set('doctrine.orm.entity_manager', $entityManager);

        $bundle = new EasyMediaBundle();
        $bundle->setContainer($container);
        $bundle->boot();

        $type = new EasyMediaType();

        self::assertSame($media, $type->convertToPHPValue(33, $this->createMock(AbstractPlatform::class)));
    }
}
